-- ============================================================
-- SECONDPLAN Database Schema
-- Complete production-ready database structure
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. USER MANAGEMENT
-- ============================================================

-- Roles table
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  INDEX `idx_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`role_name`, `description`) VALUES
('admin', 'Full system access'),
('band_member', 'Band member with task and expense access'),
('customer', 'Customer with booking and merchandise access'),
('client', 'Alias for customer role');

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `email_verified` BOOLEAN DEFAULT FALSE,
  `verification_token` VARCHAR(64),
  `reset_token` VARCHAR(64),
  `reset_expires` DATETIME,
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User roles mapping
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. EVENTS MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `events` (
  `event_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `venue` VARCHAR(255) NOT NULL,
  `location` VARCHAR(255),
  `capacity` INT UNSIGNED,
  `seats_booked` INT UNSIGNED DEFAULT 0,
  `price` DECIMAL(10,2),
  `status` ENUM('scheduled', 'cancelled', 'completed', 'postponed') DEFAULT 'scheduled',
  `poster_image` VARCHAR(255),
  `created_by` INT UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_date` (`date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. BOOKINGS MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `event_id` INT UNSIGNED,
  `company_name` VARCHAR(255),
  `event_name` VARCHAR(255) NOT NULL,
  `event_date` DATE NOT NULL,
  `event_time` TIME,
  `location` VARCHAR(255),
  `address` TEXT,
  `postal_code` VARCHAR(20),
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `price` DECIMAL(10,2),
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
  `poster_event` VARCHAR(255),
  `notes` TEXT,
  `admin_notes` TEXT,
  `approved_by` INT UNSIGNED,
  `approved_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TASKS MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `assigned_to` INT UNSIGNED,
  `assigned_by` INT UNSIGNED,
  `event_id` INT UNSIGNED,
  `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  `status` ENUM('todo', 'in_progress', 'completed', 'cancelled') DEFAULT 'todo',
  `due_date` DATE,
  `due_time` TIME,
  `completed_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE SET NULL,
  INDEX `idx_assigned` (`assigned_to`),
  INDEX `idx_status` (`status`),
  INDEX `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. EXPENSES MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `expenses` (
  `expense_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `vendor` VARCHAR(255),
  `reference` VARCHAR(100),
  `description` TEXT,
  `notes` TEXT,
  `receipt` VARCHAR(255),
  `status` ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
  `submitted_by` INT UNSIGNED,
  `approved_by` INT UNSIGNED,
  `event_id` INT UNSIGNED,
  `approved_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`),
  FOREIGN KEY (`submitted_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE SET NULL,
  INDEX `idx_date` (`expense_date`),
  INDEX `idx_status` (`status`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. MERCHANDISE MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS `merchandise` (
  `merch_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) UNIQUE,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `cost` DECIMAL(10,2),
  `stock` INT UNSIGNED DEFAULT 0,
  `low_stock_threshold` INT UNSIGNED DEFAULT 10,
  `category` VARCHAR(100),
  `image` VARCHAR(255),
  `status` ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`merch_id`),
  INDEX `idx_sku` (`sku`),
  INDEX `idx_status` (`status`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. ORDERS & CART
-- ============================================================

CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) UNIQUE NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
  `payment_method` VARCHAR(50),
  `shipping_address` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_order_number` (`order_number`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `merch_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`merch_id`) REFERENCES `merchandise`(`merch_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `merch_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED DEFAULT 1,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`merch_id`) REFERENCES `merchandise`(`merch_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_cart_item` (`user_id`, `merch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255),
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. ACTIVITY LOG
-- ============================================================

CREATE TABLE IF NOT EXISTS `activity_log` (
  `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50),
  `entity_id` INT UNSIGNED,
  `details` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) UNIQUE NOT NULL,
  `value` TEXT,
  `type` VARCHAR(50) DEFAULT 'string',
  `description` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user (password: Admin@123)
INSERT INTO `users` (`email`, `password_hash`, `name`, `status`, `email_verified`) VALUES
('admin@secondplan.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'active', TRUE);

-- Assign admin role
INSERT INTO `user_roles` (`user_id`, `role_id`) 
SELECT u.user_id, r.role_id 
FROM users u, roles r 
WHERE u.email = 'admin@secondplan.local' AND r.role_name = 'admin';

-- Default settings
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('site_name', 'SecondPlan', 'string', 'Website name'),
('site_email', 'info@secondplan.local', 'string', 'Contact email'),
('timezone', 'Asia/Kuala_Lumpur', 'string', 'Default timezone'),
('currency', 'MYR', 'string', 'Currency code'),
('enable_registrations', '1', 'boolean', 'Allow new user registrations');

COMMIT;

-- ============================================================
-- VIEWS FOR REPORTING
-- ============================================================

CREATE OR REPLACE VIEW `v_booking_summary` AS
SELECT 
    DATE_FORMAT(event_date, '%Y-%m') AS month,
    status,
    COUNT(*) AS total_bookings,
    SUM(price) AS total_revenue
FROM bookings
GROUP BY month, status;

CREATE OR REPLACE VIEW `v_expense_summary` AS
SELECT 
    DATE_FORMAT(expense_date, '%Y-%m') AS month,
    category,
    SUM(amount) AS total_amount,
    COUNT(*) AS total_count
FROM expenses
WHERE status = 'approved'
GROUP BY month, category;

CREATE OR REPLACE VIEW `v_merchandise_inventory` AS
SELECT 
    m.*,
    CASE 
        WHEN m.stock = 0 THEN 'out_of_stock'
        WHEN m.stock <= m.low_stock_threshold THEN 'low_stock'
        ELSE 'in_stock'
    END AS stock_status,
    (m.stock * m.price) AS inventory_value
FROM merchandise m
WHERE m.status = 'active';
