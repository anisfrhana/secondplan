<?php
/**
 * SECONDPLAN - Bootstrap File
 * Loads configuration, session, helpers, and initializes the app
 */

// Prevent redeclaration
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load main config
require_once __DIR__ . '/config.php';

// Load core includes ONCE
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/functions.php';

// Start session
initSession();

/**
 * CSRF helpers
 */
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return generateCSRF();
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf'] ?? '';
            if (!verifyCSRF($token)) {
                http_response_code(403);
                die('CSRF token validation failed');
            }
        }
    }
}

/**
 * Auth guards
 */
if (!function_exists('require_login')) {
    function require_login() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . APP_URL . '/auth/login.php');
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($roles) {
        require_login();

        $roles = is_array($roles) ? $roles : [$roles];
        $currentRole = getUserRole();

        if (!in_array($currentRole, $roles) && $currentRole !== ROLE_ADMIN) {
            http_response_code(403);
            die('Access denied');
        }
    }
}
