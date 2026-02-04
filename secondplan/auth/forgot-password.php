<?php
// --------------------------------------
// Minimal PHP for Forgot Password Page
// --------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Dummy placeholders (replace with real functions if needed)
$APP_NAME = 'SecondPlan';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Simulate success message
        $success = 'If the email exists, a reset link has been sent.';
    }
}

// Safe HTML escaping function
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// CSRF token placeholder
function csrf_token() {
    return 'dummy_csrf_token';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - <?= e($APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--panel) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
        }

        .login-container { width: 100%; max-width: 420px; }

        .login-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .brand-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .brand h1 { font-size: 24px; font-weight: 700; }

        .subtitle { color: var(--text-secondary); margin-bottom: 32px; font-size: 15px; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: var(--text-secondary); }
        .form-group input {
            width: 100%; padding: 12px 16px;
            background: rgba(15,23,42,0.5);
            border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-size: 15px;
        }
        .form-group input:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .btn-primary {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border: none; border-radius: 8px;
            color: white; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59,130,246,0.3);
        }

        .register-link { text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary); }
        .register-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">

        <div class="brand">
            <div class="brand-icon">🔐</div>
            <h1><?= e($APP_NAME) ?></h1>
        </div>

        <p class="subtitle">Forgot your password? No worries.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="you@example.com"
                    value="<?= e($_POST['email'] ?? '') ?>"
                >
            </div>

            <button type="submit" class="btn-primary">
                Send Reset Link
            </button>
        </form>

        <div class="register-link">
            <a href="login.php">← Back to login</a>
        </div>

    </div>
</div>
</body>
</html>
