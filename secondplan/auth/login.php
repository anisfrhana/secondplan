<?php
$title = 'Login · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT u.*, GROUP_CONCAT(r.role_name) AS roles
FROM users u
LEFT JOIN user_roles ur ON ur.user_id = u.user_id
LEFT JOIN roles r ON r.role_id = ur.role_id
WHERE email = ?
GROUP BY u.user_id");
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u || !password_verify($pass, $u['password_hash'])) {
$error = 'Invalid credentials';
} else {
$_SESSION['user_id'] = (int)$u['user_id'];
$_SESSION['name'] = $u['name'];

$roles = explode(',', $u['roles'] ?? '');
$_SESSION['role'] = $roles[0] ?: 'client';

header('Location: /index.php'); exit;
}
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Login</h1>
<?php if (!empty($error)): ?><p style="color:red"><?php echo e($error); ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
<label>Email<br><input name="email" type="email" required></label><br><br>
<label>Password<br><input name="password" type="password" required></label><br><br>
<button type="submit">Login</button>
</form>
<p><small>Admin (seed): admin@secondplan.local / Admin@123</small></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
$title = 'Login · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
verify_csrf();

$isJson = (($_SERVER['HTTP_ACCEPT'] ?? '') && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT u.*, GROUP_CONCAT(r.role_name) AS roles
FROM users u
LEFT JOIN user_roles ur ON ur.user_id = u.user_id
LEFT JOIN roles r ON r.role_id = ur.role_id
WHERE email = ?
GROUP BY u.user_id");
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u || !password_verify($pass, $u['password_hash'])) {
if ($isJson) {
http_response_code(400);
header('Content-Type: application/json');
echo json_encode(['success'=>false,'message'=>'Invalid credentials']); exit;
}
$error = 'Invalid credentials';
} else {
$_SESSION['user_id'] = (int)$u['user_id'];
$_SESSION['name'] = $u['name'];
$roles = explode(',', $u['roles'] ?? '');
$_SESSION['role'] = $roles[0] ?: 'client';

if ($isJson) {
header('Content-Type: application/json');
echo json_encode(['success'=>true,'message'=>'Login successful','redirect'=>'/index.php']); exit;
}
header('Location: /index.php'); exit;
}

echo json_encode([
  'success' => true,
  'message' => 'Login successful',
  'role' => $_SESSION['role'],
  'redirect' => '/index.php'
]);

}

exit;   
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login · SecondPlan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/css/admin.css">

<style>
:root {
  --grad: linear-gradient(135deg, #3b82f6, #22d3ee);
}

body {
  min-height: 100vh;
  background:
    radial-gradient(circle at top right, rgba(59,130,246,.15), transparent 40%),
    radial-gradient(circle at bottom left, rgba(34,211,238,.15), transparent 40%),
    #020617;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: Inter, system-ui;
}

.login-wrapper {
  width: 100%;
  max-width: 420px;
  padding: 20px;
}

.login-card {
  background: rgba(15,23,42,.85);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 36px;
  border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 30px 60px rgba(0,0,0,.45);
  animation: pop .5s ease;
}

@keyframes pop {
  from { transform: scale(.95); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
}

.brand-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--grad);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
  box-shadow: 0 10px 30px rgba(59,130,246,.4);
}

.brand h1 {
  font-size: 22px;
  margin: 0;
}

.subtitle {
  color: var(--text-secondary);
  margin-bottom: 26px;
  font-size: 14px;
}

.field {
  margin-bottom: 18px;
}

.field label {
  display: block;
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 6px;
}

.input-wrap {
  position: relative;
}

.field input {
  width: 100%;
  padding: 13px 44px 13px 14px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.08);
  background: rgba(30,41,59,.4);
  color: var(--text-primary);
  transition: .2s;
}

.field input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59,130,246,.15);
}

.toggle-pass {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 14px;
  color: var(--text-secondary);
}

.error {
  display: none;
  background: rgba(239,68,68,.12);
  border: 1px solid rgba(239,68,68,.3);
  color: #fecaca;
  padding: 12px;
  border-radius: 12px;
  font-size: 14px;
  margin-bottom: 16px;
  animation: shake .4s;
}

@keyframes shake {
  25% { transform: translateX(-4px); }
  50% { transform: translateX(4px); }
  75% { transform: translateX(-2px); }
}

.btn-login {
  width: 100%;
  padding: 14px;
  border-radius: 14px;
  background: var(--grad);
  border: none;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: .2s;
  position: relative;
  overflow: hidden;
}

.btn-login.loading {
  pointer-events: none;
  opacity: .7;
}

.btn-login:hover {
  transform: translateY(-1px);
  box-shadow: 0 15px 40px rgba(59,130,246,.4);
}

.footer {
  margin-top: 18px;
  text-align: center;
  font-size: 12px;
  color: var(--text-secondary);
}

.role-badge {
  display: none;
  margin: 18px auto 0;
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  width: fit-content;
  animation: fadeUp .4s ease;
}

.role-admin {
  background: rgba(59,130,246,.15);
  color: #93c5fd;
  border: 1px solid rgba(59,130,246,.4);
}

.role-client {
  background: rgba(34,197,94,.15);
  color: #86efac;
  border: 1px solid rgba(34,197,94,.4);
}

@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

</style>
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">
    <div class="brand">
      <div class="brand-icon">⚡</div>
      <h1>SecondPlan</h1>
    </div>

    <p class="subtitle">Welcome back 👋 Please sign in to continue</p>

    <div id="errorBox" class="error"></div>

    <form id="loginForm">
      <input type="hidden" name="csrf" id="csrfToken">

      <div class="field">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="input-wrap">
          <input type="password" name="password" id="password" required>
          <span class="toggle-pass" onclick="togglePass()">👁️</span>
        </div>
      </div>

      <button class="btn-login" id="loginBtn ">
        Sign In
      </button>

      <div id="roleBadge" class="role-badge"></div>

    </form>

    <div class="footer">
      Admin seed: admin@secondplan.local / Admin@123
    </div>
  </div>
</div>

<script src="assets/js/login.js"></script>

</body>
</html>
