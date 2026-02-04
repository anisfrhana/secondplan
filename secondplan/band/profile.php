<?php
require_once __DIR__ . '/../config/config.php';
initSession();
requireLogin();
requireRole(ROLE_BAND);

$userData = getUserData();
?>

<h1>My Profile</h1>
<p>Name: <?= e($userData['name']) ?></p>
<p>Email: <?= e($userData['email']) ?></p>
<p>Role: <?= e($userData['role']) ?></p>
<p>Joined At: <?= e($userData['created_at']) ?></p>