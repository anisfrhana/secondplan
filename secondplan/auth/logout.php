<?php
/**
 * SECONDPLAN - Logout
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once INCLUDES_PATH . '/auth_functions.php';

// Log activity if logged in
if (isLoggedIn()) {
    $userId = getUserId();
    logActivity($userId, 'logout', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}

// Destroy all session and cookies
destroySession();

// Redirect to login page
header('Location: ' . APP_URL . '/auth/login.php');
exit;
