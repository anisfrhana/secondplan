<?php
/**
 * SECONDPLAN - Configuration File
 * Core application settings and constants
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'secondplan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'SECONDPLAN');
define('APP_URL', 'http://localhost/secondplan_rebuild');
define('APP_VERSION', '2.0.0');

// Security Settings
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('ASSETS_PATH', BASE_PATH . '/assets');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_BAND', 'band');
define('ROLE_USER', 'user');

// Status Constants
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_COMPLETED', 'completed');
define('STATUS_CANCELLED', 'cancelled');

// Email Configuration (if needed)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@secondplan.com');
define('SMTP_PASS', '');
define('SMTP_FROM', 'SECONDPLAN <noreply@secondplan.com>');

/**
 * Initialize database connection with PDO
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

/**
 * Autoload core includes
 */
function autoloadIncludes() {
    require_once BASE_PATH . '/includes/session.php';
    require_once BASE_PATH . '/includes/functions.php';
}

// Auto-initialize on include
autoloadIncludes();
