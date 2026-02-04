<?php
/**
 * SECONDPLAN - Core Configuration
 * All application settings and constants
 */

// ===========================================
// DATABASE CONFIGURATION
// ===========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'secondplan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===========================================
// APPLICATION SETTINGS
// ===========================================
define('APP_NAME', 'SECONDPLAN');
define('APP_URL', 'http://localhost/secondplan');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development'); // development, production
// Prevent direct access
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

// ===========================================
// ERROR REPORTING
// ===========================================
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// ===========================================
// TIMEZONE
// ===========================================
date_default_timezone_set('Asia/Kuala_Lumpur');


// ===========================================
// SECURITY SETTINGS
// ===========================================
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// ===========================================
// FILE UPLOAD SETTINGS
// ===========================================
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// ===========================================
// PATHS
// ===========================================
define('ASSETS_PATH', BASE_PATH . '/assets');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('CONFIG_PATH', BASE_PATH . '/config');

// ===========================================
// USER ROLES
// ===========================================
define('ROLE_ADMIN', 'admin');
define('ROLE_BAND', 'band');
define('ROLE_MEMBER', 'member');
define('ROLE_CLIENT', 'client');
define('ROLE_CUSTOMER', 'customer');

// ===========================================
// STATUS CONSTANTS
// ===========================================
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_COMPLETED', 'completed');
define('STATUS_CANCELLED', 'cancelled');

// ===========================================
// EMAIL CONFIGURATION (Optional)
// ===========================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@secondplan.com');
define('SMTP_FROM_NAME', 'SecondPlan System');

// ===========================================
// PAGINATION
// ===========================================
define('ITEMS_PER_PAGE', 20);

// ===========================================
// AUTO-LOAD FUNCTIONS
// ===========================================
if (file_exists(INCLUDES_PATH . '/database.php')) {
    require_once INCLUDES_PATH . '/database.php';
}
if (file_exists(INCLUDES_PATH . '/session.php')) {
    require_once INCLUDES_PATH . '/session.php';
}
if (file_exists(INCLUDES_PATH . '/functions.php')) {
    require_once INCLUDES_PATH . '/functions.php';
}
if (file_exists(INCLUDES_PATH . '/auth_functions.php')) {
    require_once INCLUDES_PATH . '/auth_functions.php';
}
