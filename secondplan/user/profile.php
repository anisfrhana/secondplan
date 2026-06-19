<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$flash = getFlash();
$errors = [];
$userId = getUserId();

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name)) $errors[] = 'Name is required';

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?")
                ->execute([$name, $phone, $userId]);
            $_SESSION['user_name'] = $name;
            logActivity($userId, 'profile_updated', ['name' => $name]);
            setFlash('success', 'Profile updated.');
            redirect('/user/profile.php');
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!verifyPassword($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($newPass) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        } elseif ($newPass !== $confirm) {
            $errors[] = 'New passwords do not match';
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
                ->execute([hashPassword($newPass), $userId]);
            logActivity($userId, 'password_changed', []);
            setFlash('success', 'Password changed.');
            redirect('/user/profile.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <div>
                <h2>My Profile</h2>
                <div class="subtitle">Manage your account settings</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
            </div>
        </header>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <div class="grid-2">
                <div class="section">
                    <h3>Profile Information</h3>
                    <form method="POST" style="margin-top:12px;">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" required value="<?= e($user['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= e($user['email']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="Phone number">
                        </div>
                        <div class="form-group">
                            <label>Member Since</label>
                            <input type="text" value="<?= formatDate($user['created_at']) ?>" disabled>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top:4px;">
                            <i class="bi bi-floppy btn-icon"></i>
                            Update Profile
                        </button>
                    </form>
                </div>

                <div class="section">
                    <h3>Change Password</h3>
                    <form method="POST" style="margin-top:12px;">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top:4px;">
                            <i class="bi bi-key btn-icon"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
