-- ==============================================================================
-- DevDock — Professional API Development & Testing Platform
-- Normalized MySQL Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.4+ / PHP 8.x PDO
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `devdock_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `devdock_db`;

-- Drop existing tables in reverse dependency order if needed
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `test_results`;
DROP TABLE IF EXISTS `automated_tests`;
DROP TABLE IF EXISTS `request_history`;
DROP TABLE IF EXISTS `environment_variables`;
DROP TABLE IF EXISTS `environments`;
DROP TABLE IF EXISTS `request_params`;
DROP TABLE IF EXISTS `request_headers`;
DROP TABLE IF EXISTS `api_requests`;
DROP TABLE IF EXISTS `collections`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. Table: users
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: collections
-- -----------------------------------------------------------------------------
CREATE TABLE `collections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_collections_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_collections_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: api_requests
-- -----------------------------------------------------------------------------
CREATE TABLE `api_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `collection_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS') NOT NULL DEFAULT 'GET',
    `url` TEXT NOT NULL,
    `auth_type` ENUM('none', 'bearer', 'basic', 'apikey') NOT NULL DEFAULT 'none',
    `auth_config` JSON NULL,
    `body_type` ENUM('none', 'json', 'raw', 'form-data', 'x-www-form-urlencoded') NOT NULL DEFAULT 'none',
    `body_content` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_requests_collection` FOREIGN KEY (`collection_id`) 
        REFERENCES `collections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_requests_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_requests_collection_id` (`collection_id`),
    INDEX `idx_requests_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: request_headers
-- -----------------------------------------------------------------------------
CREATE TABLE `request_headers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT UNSIGNED NOT NULL,
    `header_name` VARCHAR(150) NOT NULL,
    `header_value` TEXT NOT NULL,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT `fk_headers_request` FOREIGN KEY (`request_id`) 
        REFERENCES `api_requests` (`id`) ON DELETE CASCADE,
    INDEX `idx_headers_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table: request_params
-- -----------------------------------------------------------------------------
CREATE TABLE `request_params` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT UNSIGNED NOT NULL,
    `param_key` VARCHAR(150) NOT NULL,
    `param_value` TEXT NOT NULL,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT `fk_params_request` FOREIGN KEY (`request_id`) 
        REFERENCES `api_requests` (`id`) ON DELETE CASCADE,
    INDEX `idx_params_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Table: environments
-- -----------------------------------------------------------------------------
CREATE TABLE `environments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_environments_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_environments_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Table: environment_variables
-- -----------------------------------------------------------------------------
CREATE TABLE `environment_variables` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `environment_id` INT UNSIGNED NOT NULL,
    `var_key` VARCHAR(100) NOT NULL,
    `var_value` TEXT NOT NULL,
    `is_secret` TINYINT(1) NOT NULL DEFAULT 0,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT `fk_env_variables_env` FOREIGN KEY (`environment_id`) 
        REFERENCES `environments` (`id`) ON DELETE CASCADE,
    INDEX `idx_env_vars_env_id` (`environment_id`),
    INDEX `idx_env_vars_lookup` (`environment_id`, `var_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Table: request_history
-- -----------------------------------------------------------------------------
CREATE TABLE `request_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `request_id` INT UNSIGNED NULL,
    `method` VARCHAR(10) NOT NULL,
    `url` TEXT NOT NULL,
    `request_headers` JSON NULL,
    `request_body` LONGTEXT NULL,
    `status_code` INT NULL,
    `status_text` VARCHAR(100) NULL,
    `response_headers` JSON NULL,
    `response_body` LONGTEXT NULL,
    `response_time_ms` FLOAT NULL,
    `response_size_bytes` INT NULL,
    `error_message` TEXT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_request` FOREIGN KEY (`request_id`) 
        REFERENCES `api_requests` (`id`) ON DELETE SET NULL,
    INDEX `idx_history_user_id` (`user_id`),
    INDEX `idx_history_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Table: automated_tests
-- -----------------------------------------------------------------------------
CREATE TABLE `automated_tests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT UNSIGNED NOT NULL,
    `test_type` ENUM('status_code_is', 'response_time_less_than', 'json_property_exists', 'json_property_equals', 'body_contains') NOT NULL,
    `expected_value` VARCHAR(255) NOT NULL,
    `property_path` VARCHAR(255) NULL,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tests_request` FOREIGN KEY (`request_id`) 
        REFERENCES `api_requests` (`id`) ON DELETE CASCADE,
    INDEX `idx_tests_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Table: test_results
-- -----------------------------------------------------------------------------
CREATE TABLE `test_results` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `history_id` INT UNSIGNED NOT NULL,
    `test_id` INT UNSIGNED NULL,
    `test_name` VARCHAR(255) NOT NULL,
    `passed` TINYINT(1) NOT NULL DEFAULT 0,
    `actual_value` TEXT NULL,
    `error_message` TEXT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_results_history` FOREIGN KEY (`history_id`) 
        REFERENCES `request_history` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_results_test` FOREIGN KEY (`test_id`) 
        REFERENCES `automated_tests` (`id`) ON DELETE SET NULL,
    INDEX `idx_results_history_id` (`history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
