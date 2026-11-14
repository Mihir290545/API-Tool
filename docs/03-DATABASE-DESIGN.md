# DevDock — Database Design & Entity Relationship Specification

---

## 1. Schema Overview

The DevDock database (`devdock_db`) is normalized to 3NF (Third Normal Form) and implemented on **MySQL / MariaDB** using the **InnoDB** storage engine for ACID compliance and foreign key enforcement.

---

## 2. Entity Dictionary

### 2.1 `users`
Stores user identity and credentials.
* `id`: `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
* `name`: `VARCHAR(100) NOT NULL`
* `email`: `VARCHAR(191) NOT NULL UNIQUE`
* `password_hash`: `VARCHAR(255) NOT NULL`
* `created_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
* `updated_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`

### 2.2 `collections`
User-owned groupings of saved API endpoints.
* `id`: `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
* `user_id`: `INT UNSIGNED NOT NULL, FK -> users(id) ON DELETE CASCADE`
* `name`: `VARCHAR(150) NOT NULL`
* `description`: `TEXT NULL`
* `created_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
* `updated_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`

### 2.3 `api_requests`
Saved request definitions.
* `id`: `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
* `collection_id`: `INT UNSIGNED NULL, FK -> collections(id) ON DELETE CASCADE`
* `user_id`: `INT UNSIGNED NOT NULL, FK -> users(id) ON DELETE CASCADE`
* `name`: `VARCHAR(150) NOT NULL`
* `method`: `ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS') DEFAULT 'GET'`
* `url`: `TEXT NOT NULL`
* `auth_type`: `ENUM('none', 'bearer', 'basic', 'apikey') DEFAULT 'none'`
* `auth_config`: `JSON NULL`
* `body_type`: `ENUM('none', 'json', 'raw', 'form-data', 'x-www-form-urlencoded') DEFAULT 'none'`
* `body_content`: `LONGTEXT NULL`
* `created_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
* `updated_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`

### 2.4 `request_headers` & `request_params`
Normalized key-value pairs for request headers and query parameters.
* `id`: `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
* `request_id`: `INT UNSIGNED NOT NULL, FK -> api_requests(id) ON DELETE CASCADE`
* `header_name` / `param_key`: `VARCHAR(150) NOT NULL`
* `header_value` / `param_value`: `TEXT NOT NULL`
* `is_enabled`: `TINYINT(1) DEFAULT 1`

### 2.5 `environments` & `environment_variables`
Multi-profile environment configuration and variables.
* `environments`: `id`, `user_id`, `name`, `timestamps`
* `environment_variables`: `id`, `environment_id`, `var_key`, `var_value`, `is_secret`, `is_enabled`

### 2.6 `request_history`
Chronological audit log of all executed requests.
* `id`: `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
* `user_id`: `INT UNSIGNED NOT NULL, FK -> users(id) ON DELETE CASCADE`
* `request_id`: `INT UNSIGNED NULL, FK -> api_requests(id) ON DELETE SET NULL`
* `method`: `VARCHAR(10) NOT NULL`
* `url`: `TEXT NOT NULL`
* `request_headers`: `JSON NULL`
* `request_body`: `LONGTEXT NULL`
* `status_code`: `INT NULL`
* `status_text`: `VARCHAR(100) NULL`
* `response_headers`: `JSON NULL`
* `response_body`: `LONGTEXT NULL`
* `response_time_ms`: `FLOAT NULL`
* `response_size_bytes`: `INT NULL`
* `error_message`: `TEXT NULL`
* `executed_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`

### 2.7 `automated_tests` & `test_results`
Assertion rules and test execution logs.
* `automated_tests`: `id`, `request_id`, `test_type`, `expected_value`, `property_path`, `is_enabled`
* `test_results`: `id`, `history_id`, `test_id`, `test_name`, `passed`, `actual_value`, `error_message`, `executed_at`
