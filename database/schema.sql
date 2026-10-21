-- ========================================================
-- Curtiss Client Manager - Database Schema
-- Production Ready & Optimized Schema for MySQL 8 / MariaDB
-- Timezone: Asia/Colombo | Currency: LKR
-- ========================================================

CREATE DATABASE IF NOT EXISTS `trycurtiss_admin` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `trycurtiss_admin`;

-- 1. Users table (Admin & Staff)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `pin_hash` VARCHAR(255) NULL DEFAULT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'admin',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Clients table
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_code` VARCHAR(10) NOT NULL,
  `company_name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `whatsapp` VARCHAR(30) NULL,
  `email` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `curtiss_system` VARCHAR(100) NULL,
  `system_url` VARCHAR(255) NULL,
  `installation_date` DATE NULL,
  `status` ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_clients_code` (`client_code`),
  KEY `idx_clients_status` (`status`),
  KEY `idx_clients_archived` (`is_archived`, `status`),
  KEY `idx_clients_company` (`company_name`),
  KEY `idx_clients_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1 Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_act_client_id` (`client_id`, `created_at`),
  CONSTRAINT `fk_act_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Subscriptions table
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `plan_name` VARCHAR(100) NOT NULL,
  `frequency` ENUM('monthly', 'yearly', 'one_time', 'custom') NOT NULL DEFAULT 'monthly',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `next_due_date` DATE NULL,
  `status` ENUM('active', 'suspended', 'cancelled', 'expired') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_sub_client_id` (`client_id`),
  KEY `idx_sub_status_due` (`status`, `next_due_date`),
  KEY `idx_sub_due_date` (`next_due_date`),
  CONSTRAINT `fk_sub_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Invoices table
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(20) NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft', 'unpaid', 'partially_paid', 'paid', 'cancelled', 'overdue') NOT NULL DEFAULT 'unpaid',
  `payment_terms` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `pdf_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_invoices_number` (`invoice_number`),
  KEY `idx_inv_client_id` (`client_id`),
  KEY `idx_inv_status_due` (`status`, `due_date`),
  KEY `idx_inv_issue_date` (`issue_date`),
  CONSTRAINT `fk_inv_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.1 Invoice Line Items table
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT UNSIGNED NOT NULL,
  `item_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_inv_item_invoice` (`invoice_id`, `item_order`),
  CONSTRAINT `fk_inv_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Payments & Receipts table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(20) NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'bank_transfer', 'card', 'cheque') NOT NULL DEFAULT 'bank_transfer',
  `reference_no` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `custom_description` VARCHAR(255) NULL,
  `proof_path` VARCHAR(255) NULL,
  `pdf_path` VARCHAR(255) NULL,
  `status` ENUM('completed', 'cancelled') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_payments_receipt` (`receipt_number`),
  KEY `idx_pay_client_id` (`client_id`),
  KEY `idx_pay_invoice_id` (`invoice_id`),
  KEY `idx_pay_date_status` (`payment_date`, `status`),
  CONSTRAINT `fk_pay_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.1 Bank Accounts Table
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `bank_name` VARCHAR(100) NOT NULL,
  `branch` VARCHAR(100) NOT NULL,
  `account_name` VARCHAR(150) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_bank_status` (`status`, `is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.2 Reminder Logs Table
CREATE TABLE IF NOT EXISTS `reminder_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED NULL,
  `subscription_id` INT UNSIGNED NULL,
  `reminder_type` ENUM('7_days', '3_days', '1_day', 'due_today', 'overdue', 'custom') NOT NULL,
  `message` TEXT NOT NULL,
  `sent_by` INT UNSIGNED NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rem_client_type` (`client_id`, `reminder_type`, `sent_at`),
  CONSTRAINT `fk_rem_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Company & System Settings
CREATE TABLE IF NOT EXISTS `company_settings` (
  `setting_key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Seed Initial Data
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `status`) 
VALUES ('admin', 'admin@trycurtiss.com', '$2y$10$CpVa.ee2bQVF1sG/.zYBJ.gcncgVrFPn9JM4QcCXgqgizS65ZAEky', 'Curtiss Admin', 'admin', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

INSERT INTO `company_settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'Curtiss'),
('company_email', 'trycurtiss@gmail.com'),
('company_website', 'www.trycurtiss.com'),
('company_phone', '0761407875'),
('company_address', '79, Dambakanda Estate, Boyagane, Kurunegala'),
('currency', 'LKR'),
('timezone', 'Asia/Colombo'),
('bank_name', 'Commercial Bank'),
('bank_branch', 'Kurunegala City Office'),
('account_name', 'G L DASUN SUSARA SENARATHNE'),
('account_number', '8018526390')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
