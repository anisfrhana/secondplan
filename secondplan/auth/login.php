<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (isLoggedIn() && !empty(getUserRole())) {
    switch (getUserRole()) {
        case 'admin':
            redirect('/admin/dashboard.php');
        case 'band':
        case 'member':
            redirect('/band/dashboard.php');
        case 'user':
        case 'customer':
            redirect('/user/dashboard.php');
        default:
            destroySession();
            break;
    }
}

$error = '';
$success = isset($_GET['registered']) ? 'Registration successful! Please login.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } elseif (isLoginLocked($email)) {
        $remaining = ceil(LOGIN_LOCKOUT_TIME / 60);
        $error = 'Too many failed attempts. Please try again in ' . $remaining . ' minutes.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT u.*, GROUP_CONCAT(r.role_name) AS roles
                FROM users u
                LEFT JOIN user_roles ur ON ur.user_id = u.user_id
                LEFT JOIN roles r ON r.role_id = ur.role_id
                WHERE u.email = ? AND u.status = 'active'
                GROUP BY u.user_id
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                logFailedLogin($email);
                $attemptsLeft = MAX_LOGIN_ATTEMPTS - getLoginAttempts($email);
                $error = 'Invalid email or password';
                if ($attemptsLeft > 0 && $attemptsLeft <= 3) {
                    $error .= '. ' . $attemptsLeft . ' attempt(s) remaining.';
                }
            } else {
                $roles = explode(',', $user['roles'] ?? '');
                $primaryRole = strtolower(trim($roles[0] ?? 'customer'));

                if ($primaryRole === 'band_member') {
                    $primaryRole = 'member';
                }

                setUserSession(
                    (int)$user['user_id'],
                    $user['name'],
                    $user['email'],
                    $primaryRole
                );

                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

                logActivity(
                    (int)$user['user_id'],
                    'login',
                    ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
                );

                $redirectTo = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);

                if ($redirectTo) {
                    header('Location: ' . APP_URL . $redirectTo);
                    exit;
                }

                switch ($primaryRole) {
                    case 'admin':
                        header('Location: ' . APP_URL . '/admin/dashboard.php');
                        exit;
                    case 'member':
                        header('Location: ' . APP_URL . '/band/dashboard.php');
                        exit;
                    default:
                        header('Location: ' . APP_URL . '/user/dashboard.php');
                        exit;
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SecondPlan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --bg: #faf7f2;
            --bg-accent: #fff8eb;
            --panel: #ffffff;
            --border: #e5ddd0;
            --text: #1e1e1e;
            --text-secondary: #6b7280;
            --accent: #DC2626;
            --accent-hover: #B91C1C;
            --success: #22c55e;
            --error: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--bg-accent) 50%, #fecaca 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
        }
        .login-container { width: 100%; max-width: 420px; }
        .login-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(220,38,38,0.05);
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; text-decoration: none; color: inherit; }
        .brand-icon { width: 48px; height: 48px; border-radius: 12px; object-fit: cover; }
        .brand h1 { font-size: 24px; font-weight: 700; color: var(--text); }
        .subtitle { color: var(--text-secondary); margin-bottom: 32px; font-size: 15px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #86efac; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: var(--text-secondary); }
        .form-group input {
            width: 100%; padding: 12px 16px;
            background: var(--bg);
            border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-size: 15px; transition: all 0.2s;
        }
        .form-group input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15); }
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 48px; }
        .toggle-password {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-secondary);
            cursor: pointer; padding: 4px; display: flex; align-items: center;
        }
        .toggle-password:hover { color: var(--accent); }
        .toggle-password svg { width: 20px; height: 20px; }
        .btn-primary {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border: none; border-radius: 8px;
            color: #fff; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(220, 38, 38, 0.35); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
        .divider { text-align: center; margin: 24px 0; color: var(--text-secondary); font-size: 14px; }
        .register-link { text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary); }
        .register-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .register-link a:hover { text-decoration: underline; }
.remember-me { display: flex; align-items: center; gap: 8px; margin: 16px 0; }
        .remember-me input[type="checkbox"] { width: auto; cursor: pointer; }
        .remember-me label { margin: 0; cursor: pointer; font-size: 14px; color: var(--text-secondary); }
        .forgot-password { text-align: right; margin: -12px 0 20px; }
        .forgot-password a { color: var(--accent); font-size: 14px; text-decoration: none; }
        .forgot-password a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <a href="<?= APP_URL ?>/index.php" class="brand">
                <img src="<?= APP_URL ?>/assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
                <h1><?= APP_NAME ?></h1>
            </a>
            
            <p class="subtitle">Welcome back! Please sign in to continue.</p>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com" autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePasswordField('password')">
                            <i class="bi bi-eye eye-open" style="font-size:20px;"></i>
                            <i class="bi bi-eye-slash eye-closed" style="display:none;font-size:20px;"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="<?= APP_URL ?>/auth/forgot-password.php">Forgot password?</a>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn-primary" id="loginBtn">Sign In</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Create one</a>
            </div>

        </div>
        <a href="<?= APP_URL ?>/index.php" class="back-link" style="display:block;text-align:center;margin-top:20px;color:#DC2626;font-size:14px;text-decoration:none;font-weight:500;">&larr; Back to Home</a>
    </div>

    <script>
    function togglePasswordField(id) {
        var input = document.getElementById(id);
        var btn = input.parentElement.querySelector('.toggle-password');
        var eyeOpen = btn.querySelector('.eye-open');
        var eyeClosed = btn.querySelector('.eye-closed');
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            input.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        }
    }
    document.getElementById('loginForm').addEventListener('submit', function() {
        var btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.textContent = 'Signing in...';
    });
    </script>
</body>
</html>
           