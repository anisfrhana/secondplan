<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'secondplan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'SECONDPLAN');
define('APP_URL', 'http://localhost/secondplan');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development');

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

date_default_timezone_set('Asia/Kuala_Lumpur');

define('SESSION_LIFETIME', 7200);
define('CSRF_TOKEN_EXPIRE', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);
define('UPLOAD_PATH', BASE_PATH . '/uploads');

define('ASSETS_PATH', BASE_PATH . '/assets');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('CONFIG_PATH', BASE_PATH . '/config');

define('ROLE_ADMIN', 'admin');
define('ROLE_BAND', 'band');
define('ROLE_MEMBER', 'member');
define('ROLE_CLIENT', 'client');
define('ROLE_CUSTOMER', 'customer');

define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_COMPLETED', 'completed');
define('STATUS_CANCELLED', 'cancelled');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@secondplan.com');
define('SMTP_FROM_NAME', 'SecondPlan System');

define('ITEMS_PER_PAGE', 20);
