<?php
/**
 * SECONDPLAN - Utility Functions
 */

if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * Escape output (XSS protection)
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize input
 */
if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return trim(strip_tags($input));
    }
}

/**
 * Email validation
 */
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Password helpers
 */
if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}


// ========================
// CSRF Helpers
// ========================

if (!function_exists('generateCSRF')) {
    function generateCSRF(): string {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate a 32-byte random token
        $token = bin2hex(random_bytes(32));

        // Store in session with expiration
        $_SESSION['csrf_token']   = $token;
        $_SESSION['csrf_expire']  = time() + (defined('CSRF_TOKEN_EXPIRE') ? CSRF_TOKEN_EXPIRE : 3600);

        return $token;
    }
}

if (!function_exists('verifyCSRF')) {
    /**
     * Verify CSRF token from form submission
     * @param string|null $token Token from POST data
     * @return bool
     */
    function verifyCSRF(?string $token): bool {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate token
        if (
            empty($token) ||
            empty($_SESSION['csrf_token']) ||
            empty($_SESSION['csrf_expire'])
        ) {
            return false;
        }

        // Check match and expiration
        $isValid = hash_equals($_SESSION['csrf_token'], $token) &&
                   $_SESSION['csrf_expire'] >= time();

        // Optional: Unset token after verification to prevent reuse
        if ($isValid) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_expire']);
        }

        return $isValid;
    }
}


/**
 * Redirect helper
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . APP_URL . $url);
        exit;
    }
}

/**
 * Auth helpers
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('getUserRole')) {
    function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
}

if (!function_exists('currentUser')) {
    function currentUser() {
        return $_SESSION['user'] ?? null;
    }
}

/**
 * Date helpers
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd M Y') {
        if (!$date) return '-';
        return date($format, strtotime($date));
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime, $format = 'd M Y, h:i A') {
        if (!$datetime) return '-';
        return date($format, strtotime($datetime));
    }
}

/**
 * Money format (MYR)
 */
if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return 'RM ' . number_format((float)$amount, 2);
    }
}

/**
 * JSON responses
 */
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('jsonSuccess')) {
    function jsonSuccess($message, $data = null) {
        jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
}

if (!function_exists('jsonError')) {
    function jsonError($message, $status = 400) {
        jsonResponse([
            'success' => false,
            'message' => $message
        ], $status);
    }
}

/**
 * Debug helper
 */
if (!function_exists('dd')) {
    function dd($var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        die;
    }
}
