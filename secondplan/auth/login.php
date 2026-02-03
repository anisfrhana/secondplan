<?php
/**
 * SECONDPLAN - Login Page
 * Handles user authentication with role-based access
 */

require_once __DIR__ . '/../config/bootstrap.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin':
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            break;
        case 'member':
            header('Location: ' . APP_URL . '/user/dashboard.php');
            break;
        default:
            header('Location: ' . APP_URL . '/index.php');
    }
    exit;
}

$error = '';
$success = isset($_GET['registered']) ? 'Registration successful! Please login.' : '';

// Check for JSON request
$isJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
        if ($isJson) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    } else {
        try {
            // Query user with roles
            $stmt = $pdo->prepare("
                SELECT u.*, GROUP_CONCAT(r.role_name) AS roles
                FROM users u
                LEFT JOIN user_roles ur ON ur.user_id = u.user_id
                LEFT JOIN roles r ON r.role_id = ur.role_id
                WHERE u.email = ? AND u.status = 'active'
                GROUP BY u.user_id
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify password
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Invalid email or password';
                if ($isJson) {
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                }
            } else {
                // Get primary role (first role in list)
                $roles = explode(',', $user['roles'] ?? '');
                $primaryRole = $roles[0] ?? 'client';
                
                // Set session
                setUserSession(
                    (int)$user['user_id'],
                    $user['name'],
                    $user['email'],
                    $primaryRole
                );
                
                // Log activity
                logActivity($user['user_id'], 'login', ['ip' => $_SERVER['REMOTE_ADDR']]);
                
                // Determine redirect URL based on role
                $redirectUrl = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);
                
                if (!$redirectUrl) {
                    switch ($primaryRole) {
                        case 'admin':
                            $redirectUrl = '/admin/dashboard.php';
                            break;
                        case 'member':
                            $redirectUrl = '/user/dashboard.php';
                            break;
                        default:
                            $redirectUrl = '/index.php';
                    }
                }
                
                if ($isJson) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'role' => $primaryRole,
                        'redirect' => $redirectUrl
                    ]);
                    exit;
                }
                
                header('Location: ' . APP_URL . $redirectUrl);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
            if ($isJson) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }
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
    <style>
        :root {
            --bg: #0f172a;
            --panel: #1e293b;
            --border: #334155;
            --text: #e5e7eb;
            --text-secondary: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #22c55e;
            --error: #ef4444;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        
        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .brand h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 32px;
            font-size: 15px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px 8px;
            font-size: 20px;
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), #2563eb);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .divider {
            text-align: center;
            margin: 24px 0;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .register-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-accounts {
            margin-top: 32px;
            padding: 20px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: 8px;
        }
        
        .demo-accounts h3 {
            font-size: 14px;
            margin-bottom: 12px;
            color: var(--text-secondary);
        }
        
        .demo-account {
            padding: 8px 0;
            font-size: 13px;
            font-family: monospace;
        }
        
        .demo-account strong {
            color: var(--accent);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 16px 0;
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .remember-me label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .forgot-password {
            text-align: right;
            margin: -12px 0 20px;
        }
        
        .forgot-password a {
            color: var(--accent);
            font-size: 14px;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Brand -->
            <div class="brand">
                <div class="brand-icon">⚡</div>
                <h1><?= APP_NAME ?></h1>
            </div>
            
            <p class="subtitle">Welcome back! Please sign in to continue.</p>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        autofocus
                        value="<?= e($_POST['email'] ?? '') ?>"
                        placeholder="you@example.com"
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            👁️
                        </button>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="btn-primary" id="loginBtn">
                    Sign In
                </button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
            
            <!-- Demo Accounts (Remove in production) -->
            <?php if (APP_ENV === 'development'): ?>
            <div class="demo-accounts">
                <h3>Demo Accounts:</h3>
                <div class="demo-account">
                    <strong>Admin:</strong> admin@secondplan.local / Admin@123
                </div>
                <div class="demo-account">
                    <strong>Band:</strong> band@secondplan.local / Band@123
                </div>
                <div class="demo-account">
                    <strong>User:</strong> user@secondplan.local / User@123
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="<?= APP_URL ?>/assets/js/auth.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }
        
        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.disabled = true;
            loginBtn.textContent = 'Signing in...';
        });
    </script>
</body>
</html>
           
