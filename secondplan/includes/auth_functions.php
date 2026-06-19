<?php
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

if (!function_exists('logActivity')) {
    function logActivity(int $userId, string $action, array $details = []): void {
        global $pdo;

        if (!$pdo) {
            error_log("logActivity failed: PDO connection not found.");
            return;
        }

        try {
            // Verify user exists to avoid FK constraint violation
            $check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $check->execute([$userId]);
            $validUserId = $check->fetchColumn() ? $userId : null;

            $stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, details, ip_address, user_agent)
                VALUES (:user_id, :action, :details, :ip, :ua)
            ");
            $stmt->execute([
                ':user_id' => $validUserId,
                ':action' => $action,
                ':details' => json_encode($details),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("logActivity failed: " . $e->getMessage());
        }
    }
}

if (!function_exists('getLoginAttempts')) {
    function getLoginAttempts(string $email): int {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM activity_log
            WHERE action = 'login_failed'
            AND JSON_EXTRACT(details, '$.email') = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$email, LOGIN_LOCKOUT_TIME]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('isLoginLocked')) {
    function isLoginLocked(string $email): bool {
        return getLoginAttempts($email) >= MAX_LOGIN_ATTEMPTS;
    }
}

if (!function_exists('logFailedLogin')) {
    function logFailedLogin(string $email): void {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, user_agent)
            VALUES (NULL, 'login_failed', :details, :ip, :ua)
        ");
        $stmt->execute([
            ':details' => json_encode(['email' => $email]),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}
