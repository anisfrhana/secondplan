<?php
/**
 * SECONDPLAN - Utility Functions
 * Helper functions used throughout the application
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * Sanitize output (prevent XSS)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(strip_tags($input));
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format currency (Malaysian Ringgit)
 */
function formatMoney($amount) {
    return 'RM ' . number_format((float)$amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd M Y') {
    if (!$date) return '-';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    if (!$datetime) return '-';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($timestamp);
}

/**
 * Redirect to URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . APP_URL . $url);
    exit;
}

/**
 * Return JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Return success JSON
 */
function jsonSuccess($message, $data = null) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

/**
 * Return error JSON
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * Validate file upload
 */
function validateFile($file, $allowedTypes, $maxSize = UPLOAD_MAX_SIZE) {
    $errors = [];
    
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'No file uploaded';
        return $errors;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds ' . formatBytes($maxSize);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file type';
    }
    
    return $errors;
}

/**
 * Upload file
 */
function uploadFile($file, $destination, $allowedTypes, $prefix = '') {
    $errors = validateFile($file, $allowedTypes);
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Create directory if it doesn't exist
    $uploadDir = UPLOAD_PATH . '/' . $destination;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'errors' => ['Failed to upload file']];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'path' => '/' . $destination . '/' . $filename
    ];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    $fullPath = UPLOAD_PATH . $filepath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get status badge class
 */
function getStatusClass($status) {
    $status = strtolower($status);
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'success',
        'cancelled' => 'danger',
        'paid' => 'success',
        'unpaid' => 'warning'
    ];
    return $classes[$status] ?? 'info';
}

/**
 * Generate pagination HTML
 */
function pagination($currentPage, $totalPages, $url) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $html .= '<a href="' . $url . '?page=' . $prevPage . '" class="page-link">&laquo; Prev</a>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i === $currentPage) ? 'active' : '';
        $html .= '<a href="' . $url . '?page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $html .= '<a href="' . $url . '?page=' . $nextPage . '" class="page-link">Next &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Send email (basic implementation - can be extended)
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    $headers = [
        'From' => SMTP_FROM,
        'Reply-To' => SMTP_FROM,
        'X-Mailer' => 'PHP/' . phpversion()
    ];
    
    if ($isHtml) {
        $headers['MIME-Version'] = '1.0';
        $headers['Content-type'] = 'text/html; charset=UTF-8';
    }
    
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= $key . ': ' . $value . "\r\n";
    }
    
    return mail($to, $subject, $body, $headerString);
}

/**
 * Debug helper (only in development)
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request expects JSON
 */
function expectsJson() {
    return isAjax() ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}