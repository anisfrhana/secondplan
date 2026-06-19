<?php
require_once __DIR__ . '/../config/bootstrap.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    $validToken = (bool)$user;
}

if (!$validToken && empty($success)) {
    $error = 'Invalid or expired reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    verify_csrf();

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = hashPassword($password);
        $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?")
            ->execute([$hash, $user['user_id']]);

        logActivity($user['user_id'], 'password_reset_complete', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $success = 'Password has been reset successfully. You can now log in.';
        $validToken = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --bg: #faf7f2; --bg-accent: #fff8eb; --panel: #ffffff; --border: #e5ddd0;
            --text: #1e1e1e; --text-secondary: #6b7280;
            --accent: #DC2626; --accent-hover: #B91C1C;
            --success: #22c55e; --error: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--bg-accent) 50%, #fecaca 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            color: var(--text); padding: 20px;
        }
        .login-container { width: 100%; max-width: 420px; }
        .login-card {
            background: var(--panel);
            border: 1px solid var(--border); border-radius: 16px;
            padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.08), 0 0 0 1px rgba(220,38,38,0.05);
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; text-decoration: none; color: inherit; }
        .brand-icon { width: 48px; height: 48px; border-radius: 12px; object-fit: cover; }
        .brand h1 { font-size: 24px; font-weight: 700; color: var(--text); }
        .subtitle { color: var(--text-secondary); margin-bottom: 32px; font-size: 15px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: var(--text-secondary); }
        .form-group input {
            width: 100%; padding: 12px 16px;
            background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-size: 15px;
        }
        .form-group input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(220,38,38,0.15); }
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
            border: none; border-radius: 8px; color: #fff; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(220,38,38,0.3); }
        .register-link { text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary); }
        .register-link a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <a href="<?= APP_URL ?>/index.php" class="brand">
            <img src="<?= APP_URL ?>/assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
            <h1><?= e(APP_NAME) ?></h1>
        </a>

        <p class="subtitle">Set your new password.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
            <div class="register-link"><a href="<?= APP_URL ?>/auth/login.php">Go to login</a></div>
        <?php elseif ($error && !$validToken): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
            <div class="register-link"><a href="<?= APP_URL ?>/auth/forgot-password.php">Request new reset link</a></div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" placeholder="Minimum <?= PASSWORD_MIN_LENGTH ?> characters">
                        <button type="button" class="toggle-password" onclick="togglePasswordField('password')">
                            <i class="bi bi-eye eye-open" style="font-size:20px;"></i>
                            <i class="bi bi-eye-slash eye-closed" style="display:none;font-size:20px;"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">
                        <button type="button" class="toggle-password" onclick="togglePasswordField('confirm_password')">
                            <i class="bi bi-eye eye-open" style="font-size:20px;"></i>
                            <i class="bi bi-eye-slash eye-closed" style="display:none;font-size:20px;"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Reset Password</button>
            </form>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/index.php" class="back-link" style="display:block;text-align:center;margin-top:20px;color:#DC2626;font-size:14px;text-decoration:none;font-weight:500;">&larr; Back to Home</a>
    </div>
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
</script>
</body>
</html>
