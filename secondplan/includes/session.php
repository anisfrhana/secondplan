<?php
/**
 * SECONDPLAN - Session Management
 * Secure session handling with CSRF protection
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * Initialize secure session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_name('SECONDPLAN_SESSION');
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset(
        $_SESSION['user_id'],
        $_SESSION['user_role'],
        $_SESSION['login_time']
    );
}


/**
 * Get current user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user data
 */
function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'Admin',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user', // match session key
    ];
}

// Optional helper to fix dashboard error
if (!function_exists('getUserName')) {
    function getUserName(): string {
        return $_SESSION['user_name'] ?? 'Admin';
    }
}


/**
 * Set user session after login
 */
function setUserSession(int $userId, string $name, string $email, string $role): void {
    session_regenerate_id(true);

    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email']= $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['login_time']= time();
}

/**
 * Destroy user session (logout)
 */
function destroySession(): void {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}


/**
 * Generate CSRF token
 */
function generateCSRF() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user has required role
 */
function hasRole($requiredRole) {
    $currentRole = getUserRole();
    
    if (!$currentRole) {
        return false;
    }
    
    // Admin has access to everything
    if ($currentRole === ROLE_ADMIN) {
        return true;
    }
    
    // Check specific role
    if (is_array($requiredRole)) {
        return in_array($currentRole, $requiredRole);
    }
    
    return $currentRole === $requiredRole;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin($redirectTo = '/auth/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_URL . $redirectTo);
        exit;
    }
}

/**
 * Require specific role - show error if unauthorized
 */
function requireRole($role, $redirectTo = '/index.php') {
    requireLogin();
    
    if (!hasRole($role)) {
        http_response_code(403);
        $_SESSION['error'] = 'Unauthorized access';
        header('Location: ' . APP_URL . $redirectTo);
        exit;
    }
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Initialize session on load
initSession();