<?php
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return trim(strip_tags($input));
    }
}

if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

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

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . APP_URL . $url);
        exit;
    }
}

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

if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return 'RM ' . number_format((float)$amount, 2);
    }
}

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

if (!function_exists('dd')) {
    function dd($var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        die;
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($file, $allowedTypes = null, $maxSize = null) {
        if ($allowedTypes === null) {
            $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES);
        }
        if ($maxSize === null) {
            $maxSize = UPLOAD_MAX_SIZE;
        }

        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload failed or no file provided'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File exceeds maximum size of ' . ($maxSize / 1024 / 1024) . 'MB'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            return ['success' => false, 'error' => 'File type not allowed: ' . $mime];
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $ext = $extensions[$mime] ?? 'bin';
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        $uploadDir = UPLOAD_PATH . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        return ['success' => true, 'filename' => $filename, 'mime' => $mime, 'size' => $file['size']];
    }
}

if (!function_exists('getSetting')) {
    function getSetting($key, $default = null) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }
}

if (!function_exists('setSetting')) {
    function setSetting($key, $value) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()");
        $stmt->execute([$key, $value]);
    }
}

if (!function_exists('createNotification')) {
    function createNotification($userId, $type, $title, $message, $link = null) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $title, $message, $link]);
    }
}

if (!function_exists('getUnreadNotificationCount')) {
    function getUnreadNotificationCount($userId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber() {
        return 'SP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}

if (!function_exists('generateQuotationNumber')) {
    function generateQuotationNumber() {
        return 'QT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    }
}

if (!function_exists('generateInvoiceNumber')) {
    function generateInvoiceNumber() {
        return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    }
}
