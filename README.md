# DevDock — API Development, Testing & Management Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Zero-Cost](https://img.shields.io/badge/Budget-%E2%82%B90%20(Free%20%26%20Open%20Source)-success.svg)](#free-technology-policy)

**DevDock** is a web-based, professional API development, testing, and lifecycle management platform designed as an internship portfolio project for **Yuva Intern**. Built with native PHP 8.x, MySQL (PDO), and vanilla JavaScript, DevDock offers a developer experience inspired by tools like Postman and Insomnia—with **₹0 budget and zero paid dependencies**.

---

## 🌟 Core Features

### 1. API Request Builder
* **All Major HTTP Verbs**: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`.
* **Dynamic Parameter & Header Builders**: Key-value tables with instant toggle and deletion.
* **JSON & Raw Body Editor**: Built-in JSON syntax validator and indentation beautifier.
* **Asynchronous Fetching**: Non-blocking client-side execution with animated status and progress indicators.

### 2. API Response Analyzer
* **Metrics Dashboard**: Exact HTTP status codes (with semantic color tokens), execution latency (ms), and download payload size (bytes).
* **Syntax Highlighting & Formatting**: Auto-detected JSON pretty printing with raw text toggle.
* **Response Headers Table**: Clean, searchable inspection of all returned response headers.
* **One-Click Copy**: Instant clipboard copy with animated visual confirmation.

### 3. Authentication Management
* **Flexible Authentication Modes**:
  * **No Auth**
  * **Bearer Token** (`Authorization: Bearer <token>`)
  * **Basic Auth** (`Authorization: Basic base64(user:pass)`)
  * **API Key** (Injectable via custom Header or URL Query Parameter)
* **Secret Protection**: Password input masking in the browser.

### 4. Collection Management
* Organize API endpoints into logical collections.
* Save, update, rename, and delete requests.
* Reopen any saved request into the active workspace with a single click.

### 5. Multi-Profile Environment Variables
* Configure environments (e.g. *Development*, *Staging*, *Production*).
* Define variables (e.g., `base_url`, `api_token`).
* Dynamic double-curly substitution (`{{base_url}}/users`, `{{token}}`) across URLs, headers, parameters, and bodies.

### 6. Automated API Assertion Engine
* Validate HTTP status code (e.g., Status code is `200`).
* Validate response latency (e.g., Latency < `500ms`).
* Validate JSON property existence (e.g., `data.id`).
* Validate JSON property values (e.g., `status` equals `active`).
* Validate body substring match (e.g., Contains `"success"`).
* Real-time pass/fail evaluation cards with detailed failure diagnostics.

### 7. Request History & Audit
* Comprehensive execution history per user with timestamps and performance metrics.
* One-click request replay.
* History cleanup and individual execution deletion.

### 8. Enterprise-Grade Security
* **SSRF Defense**: Outbound URL validator blocking local loopback (`127.0.0.1`), link-local metadata endpoints (`169.254.169.254`), and private RFC1918 subnets.
* **SQL Injection Prevention**: 100% PDO prepared statements.
* **XSS Defense**: Strict UTF-8 escaping and input sanitization.
* **Tenant Isolation**: Multi-user relational isolation scoping all queries to the authenticated user ID.

---

## 🛠 Technology Stack

* **Backend Primary Language**: PHP 8.2 (Native, Modular, Zero Composer Bloat)
* **Database**: MySQL Community Edition / MariaDB 10.4
* **Database Driver**: PHP Data Objects (PDO) with strict error handling
* **HTTP Client**: PHP cURL Extension
* **Frontend**: HTML5, Modern CSS3 (Custom Properties & Themes), Vanilla JavaScript (ES6+ & Fetch API)
* **Local Web Server**: Apache (XAMPP for Windows)

---

## 📁 Project Architecture

```
API-Tool/
├── app/
│   ├── Controllers/
│   │   ├── ApiController.php         # Request execution, collections, history, mock
│   │   └── AuthController.php        # User registration, login, logout
│   ├── Core/
│   │   ├── Autoloader.php            # PSR-4 native class autoloader
│   │   ├── Database.php              # PDO singleton with prepared statements
│   │   ├── Env.php                   # Lightweight .env configuration parser
│   │   ├── Request.php               # HTTP request normalizer and input extractor
│   │   ├── Response.php              # Structured JSON and view responder
│   │   ├── Router.php                # RESTful router with regex parameter matching
│   │   └── Session.php               # Secure session and CSRF state manager
│   ├── Helpers/
│   │   └── Security.php              # XSS sanitization and SSRF outbound validation
│   ├── Services/
│   │   ├── AuthService.php           # User authentication and password hashing
│   │   ├── CollectionService.php     # Collections & saved requests CRUD
│   │   ├── EnvironmentService.php    # Multi-environment variable substitution
│   │   ├── HistoryService.php        # Execution history logging and replay
│   │   ├── HttpClientService.php     # Outbound cURL engine and metrics capture
│   │   └── TestRunnerService.php     # Automated assertion evaluation engine
│   └── Views/
│       └── app.php                   # Single Page Application developer interface
├── config/
│   ├── app.php                       # Application parameters and execution limits
│   └── database.php                  # PDO connection options and credentials
├── database/
│   ├── schema.sql                    # 10-table normalized relational schema
│   └── seed.php                      # Default developer account seeder
├── docs/                             # Internship technical documentation
│   ├── 01-REQUIREMENTS-ANALYSIS.md
│   ├── 02-SYSTEM-ARCHITECTURE.md
│   ├── 03-DATABASE-DESIGN.md
│   └── 04-INSTALLATION-AND-USER-GUIDE.md
├── public/
│   ├── assets/
│   │   ├── css/style.css             # Developer UI theme stylesheet
│   │   └── js/app.js                 # Frontend SPA state & event controller
│   ├── .htaccess                     # Apache clean URL rewrite rules
│   └── index.php                     # Single Front Controller entry point
├── storage/
│   ├── logs/                         # Application runtime logs
│   └── cache/                        # Temporary cached files
├── .env.example                      # Configuration environment template
├── .gitignore                        # Git ignore rules protecting secrets
└── README.md
```

---

## 🚀 Quick Start Guide (Windows / XAMPP)

### Prerequisites
* Windows 10/11
* [XAMPP](https://www.apachefriends.org/) (PHP 8.x and MySQL / MariaDB installed)

### 1. Database Setup
1. Start **Apache** and **MySQL** via XAMPP Control Panel.
2. Open terminal in the project root and import the schema:
   ```powershell
   cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql"
   ```
3. Seed the default test account:
   ```powershell
   C:\xampp\php\php.exe database\seed.php
   ```
   *Default Account:* `developer@devdock.local` | *Password:* `password123`

### 2. Configure Environment
Copy `.env.example` to `.env`:
```ini
APP_NAME=DevDock
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/api-tool/public

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devdock_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Launch Application
Navigate in your browser to:
[http://localhost/api-tool/public/](http://localhost/api-tool/public/)

---

## 🔒 Free Technology Policy

This project strictly adheres to the **₹0 Free-to-Use Policy**:
* Zero paid API keys (OpenAI, Claude, etc.)
* Zero premium plugins or UI component subscriptions
* 100% Free Open Source Software (PHP, MySQL Community Edition, Apache, Vanilla JS)
