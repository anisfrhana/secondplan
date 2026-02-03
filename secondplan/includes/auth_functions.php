<?php
// auth_functions.php
// Handles authentication, session management, and activity logging

require_once INCLUDES_PATH . '/database.php'; // $pdo must be available

// ========================
// SESSION MANAGEMENT
// ========================
if (!function_exists('startSession')) {
    function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('destroySession')) {
    function destroySession(): void {
        startSession();

        // Clear all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }
}

// ========================
// LOGIN / AUTH CHECK
// ========================
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        startSession();
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('getUserId')) {
    function getUserId(): ?int {
        startSession();
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('getUserRole')) {
    function getUserRole(): ?string {
        startSession();
        return $_SESSION['user_role'] ?? null;
    }
}

if (!function_exists('setUserSession')) {
    function setUserSession(int $userId, string $name, string $email, string $role): void {
        startSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['user_role'] = $role;
    }
}

// ========================
// ACTIVITY LOGGING
// ========================
if (!function_exists('logActivity')) {
    function logActivity(int $userId, string $action, array $details = []): void {
        global $pdo;

        if (!$pdo) {
            // Fail silently or throw error if PDO not available
            error_log("logActivity failed: PDO connection not found.");
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, user_agent)
            VALUES (:user_id, :action, :details, :ip, :ua)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => json_encode($details),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}

