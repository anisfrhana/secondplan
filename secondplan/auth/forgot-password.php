<?php
require_once __DIR__ . '/../config/bootstrap.php';

$success = '';
$error = '';
$devResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?")
                ->execute([$token, $expires, $user['user_id']]);

            logActivity($user['user_id'], 'password_reset_request', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $resetLink = APP_URL . '/auth/reset-password.php?token=' . $token;
            sendPasswordResetEmail($email, $user['name'], $resetLink);

            if (APP_ENV === 'development') {
                $devResetLink = $resetLink;
            }
        }

        $success = 'If an account with that email exists, a reset link has been sent.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - <?= e(APP_NAME) ?></title>
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
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: var(--text-secondary); }
        .form-group input {
            width: 100%; padding: 12px 16px;
            background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-size: 15px;
        }
        .form-group input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(220,38,38,0.15); }
        .btn-primary {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border: none; border-radius: 8px; color: #fff; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(220,38,38,0.35); }
        .register-link { text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary); }
        .register-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <a href="<?= APP_URL ?>/index.php" class="brand">
            <img src="<?= APP_URL ?>/assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
            <h1><?= e(APP_NAME) ?></h1>
        </a>

        <p class="subtitle">Enter your email to receive a password reset link.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
            <?php if ($devResetLink): ?>
            <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;margin-bottom:20px;">
                <div style="font-size:12px;font-weight:600;color:#92400e;margin-bottom:8px;">DEV MODE - Reset Link:</div>
                <a href="<?= e($devResetLink) ?>" style="font-size:13px;color:#DC2626;word-break:break-all;"><?= e($devResetLink) ?></a>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-primary">Send Reset Link</button>
        </form>

        <div class="register-link">
            <a href="<?= APP_URL ?>/auth/login.php">Back to login</a>
        </div>
        <a href="<?= APP_URL ?>/index.php" class="back-link" style="display:block;text-align:center;margin-top:20px;color:#DC2626;font-size:14px;text-decoration:none;font-weight:500;">&larr; Back to Home</a>
    </div>
</div>
</body>
</html>
