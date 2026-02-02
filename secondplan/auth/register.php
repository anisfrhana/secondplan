<?php
$title = 'Register  SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
verify_csrf();

$isJson = (($_SERVER['HTTP_ACCEPT'] ?? '') && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $name  = trim($_POST['name'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $role  = $_POST['role'] ?? 'client';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email';
  elseif (strlen($pass) < 6) $error = 'Password too short';
  elseif (!$name) $error = 'Name required';
  elseif (!in_array($role, ['client','customer','member'], true)) $error = 'Invalid role';

  if (!empty($error)) {
    if ($isJson) { http_response_code(400); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$error]); exit; }
  } else {
    $q = $pdo->prepare("SELECT 1 FROM users WHERE email=?");
    $q->execute([$email]);
    if ($q->fetch()) {
      if ($isJson) { http_response_code(409); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Email already registered']); exit; }
      $error = 'Email already registered';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $roleId = match ($role) {
        'client'   => 2,
        'customer' => 3,
        'member'   => 4,
        default    => 2
      };
      $pdo->beginTransaction();
      try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, status) VALUES (?,?,?, 'active')");
        $stmt->execute([$email, $hash, $name]);
        $uid = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)")->execute([$uid, $roleId]);

        $pdo->commit();

        if ($isJson) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'message'=>'Registration successful. Please login.']); exit; }
        header('Location: /auth/login.php?ok=1'); exit;
      } catch (Throwable $t) {
        $pdo->rollBack();
        if ($isJson) { http_response_code(500); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Registration failed']); exit; }
        $error = 'Registration failed';
      }
    }
  }
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Register</h1>
<?php if (!empty($error)): ?><p style="color:red"><?php echo e($error); ?></p><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
  <label>Email<br><input name="email" type="email" required></label><br><br>
  <label>Name<br><input name="name" required></label><br><br>
  <label>Password<br><input name="password" type="password" minlength="6" required></label><br><br>
  <label>Account Type<br>
    <select name="role" required>
      <option value="client">Client (Booking)</option>
      <option value="customer">Customer (Merch)</option>
      <option value="member">Band Member</option>
    </select>
  </label><br><br>
  <button type="submit">Create Account</button>
</form>
<?php include __DIR__ . '/../includes/footer.php';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register - SecondPlan</title>
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <div class="card">
    <div class="header">
      <span class="dot"></span><h1>Create your account</h1>
    </div>
    <div class="sub">Register to access the admin dashboard</div>

    <form id="register-form" class="form" autocomplete="off">
      <!-- If you use CSRF on server, include hidden input "csrf" here -->
      <div class="field">
        <label for="name">Full Name</label>
        <input id="name" name="name" class="input" placeholder="Your name" required>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" class="input" type="email" placeholder="you@example.com" required>
      </div>

      <div class="field password-wrap">
        <label for="password">Password</label>
        <input id="password" name="password" class="input" type="password" placeholder="At least 8 characters" required>
        <button id="toggle-pass" class="toggle-pass">Show</button>
      </div>

      <div class="field password-wrap">
        <label for="confirm">Confirm Password</label>
        <input id="confirm" class="input" type="password" placeholder="Re-enter password" required>
        <button id="toggle-confirm" class="toggle-pass">Show</button>
      </div>

      <div class="row">
        <span class="help">Already have an account? <a class="link" href="login.html">Sign in</a></span>
      </div>

      <div id="notice" class="notice"></div>

      <div class="actions">
        <button id="register-btn" class="btn success" type="submit">Create Account</button>
      </div>
    </form>
  </div>

  <script src="assets/js/auth.js"></script>
</body>
</html>