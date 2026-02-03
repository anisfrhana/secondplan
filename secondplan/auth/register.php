<?php
/**
 * SECONDPLAN - Register Page
 */

require_once __DIR__ . '/../config/bootstrap.php';

// Redirect if logged in
if (isLoggedIn()) {
    redirect('/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if ($name === '') $errors[] = 'Name is required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    if ($password !== $confirm) $errors[] = 'Passwords do not match';

    if (!in_array($role, ['user', 'member'])) {
        $errors[] = 'Invalid account type';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'Email already registered';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $name,
                $email,
                $phone,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            ]);

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

<style>
:root {
    --bg:#0f172a;
    --panel:#1e293b;
    --border:#334155;
    --text:#e5e7eb;
    --text-secondary:#94a3b8;
    --accent:#3b82f6;
    --accent-hover:#2563eb;
    --error:#ef4444;
}

*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:system-ui,-apple-system,Segoe UI,Roboto;
    background:linear-gradient(135deg,var(--bg),#1e293b);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--text);
    padding:20px;
}

.register-container{width:100%;max-width:420px}

.register-card{
    background:rgba(30,41,59,.8);
    backdrop-filter:blur(20px);
    border:1px solid var(--border);
    border-radius:16px;
    padding:40px;
    box-shadow:0 20px 50px rgba(0,0,0,.5);
}

.brand{display:flex;align-items:center;gap:12px;margin-bottom:32px}
.brand-icon{
    width:48px;height:48px;
    background:linear-gradient(135deg,var(--accent),#8b5cf6);
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
}
.brand h1{font-size:24px;font-weight:700}

.subtitle{
    color:var(--text-secondary);
    margin-bottom:32px;
    font-size:15px;
}

.alert{
    padding:12px 16px;
    border-radius:8px;
    margin-bottom:20px;
    font-size:14px;
    background:rgba(239,68,68,.1);
    border:1px solid rgba(239,68,68,.3);
    color:#fca5a5;
}

.form-group{margin-bottom:20px}
.form-group label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
    color:var(--text-secondary);
}

.form-group input,
.form-group select{
    width:100%;
    padding:12px 16px;
    background:rgba(15,23,42,.5);
    border:1px solid var(--border);
    border-radius:8px;
    color:var(--text);
    font-size:15px;
}

.password-wrapper{position:relative}
.toggle-password{
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);
    background:none;
    border:none;
    color:var(--text-secondary);
    cursor:pointer;
    font-size:20px;
}

.btn-primary{
    width:100%;
    padding:14px;
    background:linear-gradient(135deg,var(--accent),#2563eb);
    border:none;
    border-radius:8px;
    color:white;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
}

.register-link{
    text-align:center;
    margin-top:20px;
    font-size:14px;
    color:var(--text-secondary);
}
.register-link a{color:var(--accent);text-decoration:none}
</style>
</head>

<body>
<div class="register-container">
<div class="register-card">

<div class="brand">
    <div class="brand-icon">⚡</div>
    <h1>SecondPlan</h1>
</div>

<p class="subtitle">Create your account to get started</p>

<?php if ($errors): ?>
<div class="alert">
    <ul>
        <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
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

<div class="form-group">
<label>Account Type</label>
<select name="role">
<option value="user">User (Booking & Merch)</option>
<option value="member">Band Member</option>
</select>
</div>

<div class="form-group">
<label>Password</label>
<div class="password-wrapper">
<input type="password" id="password" name="password" required>
<button type="button" class="toggle-password" onclick="togglePassword('password')">👁️</button>
</div>
</div>

<div class="form-group">
<label>Confirm Password</label>
<div class="password-wrapper">
<input type="password" id="confirm_password" name="confirm_password" required>
<button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
</div>
</div>

<button class="btn-primary">Create Account</button>
</form>

<div class="register-link">
Already have an account? <a href="login.php">Log in</a>
</div>

</div>
</div>

<script>
function togglePassword(id){
    const el=document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
