<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = sanitize($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    if ($name === '') $errors[] = 'Name is required';
    if ($email === '' || !isValidEmail($email)) $errors[] = 'Valid email is required';
    if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    if ($password !== $confirm) $errors[] = 'Passwords do not match';
    if ($role !== 'customer') $errors[] = 'Invalid account type';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'Email already registered';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $email, $phone, hashPassword($password)]);

            $userId = $pdo->lastInsertId();

            $roleStmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) SELECT ?, role_id FROM roles WHERE role_name = ?");
            $roleStmt->execute([$userId, $role]);

            logActivity((int)$userId, 'register', ['role' => $role]);

            redirect('/auth/login.php?registered=1');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SecondPlan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --bg:#faf7f2; --bg-accent:#fff8eb; --panel:#ffffff; --border:#e5ddd0;
            --text:#1e1e1e; --text-secondary:#6b7280;
            --accent:#DC2626; --accent-hover:#B91C1C; --error:#ef4444;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:system-ui,-apple-system,Segoe UI,Roboto;
            background:linear-gradient(135deg,var(--bg) 0%,var(--bg-accent) 50%,#fecaca 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            color:var(--text); padding:20px;
        }
        .register-container { width:100%; max-width:420px; }
        .register-card {
            background:var(--panel);
            border:1px solid var(--border); border-radius:16px;
            padding:40px; box-shadow:0 20px 50px rgba(0,0,0,.08),0 0 0 1px rgba(220,38,38,.05);
        }
        .brand { display:flex; align-items:center; gap:12px; margin-bottom:32px; text-decoration:none; color:inherit; }
        .brand-icon { width:48px; height:48px; border-radius:12px; object-fit:cover; }
        .brand h1 { font-size:24px; font-weight:700; color:var(--text); }
        .subtitle { color:var(--text-secondary); margin-bottom:32px; font-size:15px; }
        .alert {
            padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;
            background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); color:#fca5a5;
        }
        .alert ul { margin-left:16px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-size:14px; color:var(--text-secondary); }
        .form-group input, .form-group select {
            width:100%; padding:12px 16px;
            background:var(--bg); border:1px solid var(--border); border-radius:8px;
            color:var(--text); font-size:15px;
        }
        .form-group input:focus, .form-group select:focus {
            outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(220,38,38,0.15);
        }
        .password-wrapper { position:relative; }
        .password-wrapper input { padding-right:48px; }
        .toggle-password {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:var(--text-secondary);
            cursor:pointer; padding:4px; display:flex; align-items:center;
        }
        .toggle-password:hover { color:var(--accent); }
        .toggle-password svg { width:20px; height:20px; }
        .btn-primary {
            width:100%; padding:14px;
            background:linear-gradient(135deg,var(--accent),var(--accent-hover));
            border:none; border-radius:8px; color:#fff; font-size:15px; font-weight:600;
            cursor:pointer; transition:all 0.2s;
        }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 10px 25px rgba(220,38,38,0.35); }
        .register-link { text-align:center; margin-top:20px; font-size:14px; color:var(--text-secondary); }
        .register-link a { color:var(--accent); text-decoration:none; }
    </style>
</head>
<body>
<div class="register-container">
<div class="register-card">
    <a href="<?= APP_URL ?>/index.php" class="brand">
        <img src="<?= APP_URL ?>/assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
        <h1>SecondPlan</h1>
    </a>

    <p class="subtitle">Create your account to get started</p>

    <?php if ($errors): ?>
    <div class="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
        </div>

        <input type="hidden" name="role" value="customer">

        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                <button type="button" class="toggle-password" onclick="togglePasswordField('password')">
                    <i class="bi bi-eye eye-open" style="font-size:20px;"></i>
                    <i class="bi bi-eye-slash eye-closed" style="display:none;font-size:20px;"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="button" class="toggle-password" onclick="togglePasswordField('confirm_password')">
                    <i class="bi bi-eye eye-open" style="font-size:20px;"></i>
                    <i class="bi bi-eye-slash eye-closed" style="display:none;font-size:20px;"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary">Create Account</button>
    </form>

    <div class="register-link">
        Already have an account? <a href="<?= APP_URL ?>/auth/login.php">Log in</a>
    </div>
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
