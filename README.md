# DevDock — API Development, Testing & Management Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Zero-Cost](https://img.shields.io/badge/Budget-%E2%82%B90%20(Free%20%26%20Open%20Source)-success.svg)](#free-technology-policy)

**DevDock** is a modern, lightweight, web-based API development, testing, and lifecycle management platform. Built with native PHP 8.x, MySQL (PDO), and vanilla JavaScript, DevDock offers a fast, zero-dependency alternative to desktop API clients like Postman and Insomnia—running seamlessly on any standard LAMP stack or free shared web hosting (such as InfinityFree).

---

## 🌟 Core Features

### 1. API Request Composer
* **All Major HTTP Verbs**: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`.
* **Dynamic Parameter & Header Builders**: Interactive key-value tables with instant toggle and deletion.
* **JSON & Raw Body Editor**: Built-in JSON syntax validator and indentation beautifier.
* **cURL Code Generator**: Export any configured request into an executable cURL snippet ready for terminal use.

### 2. API Response Analyzer
* **Metrics Dashboard**: Exact HTTP status codes (with semantic color tokens), execution latency (ms), and download payload size (bytes).
* **Syntax Highlighting & Formatting**: Auto-detected JSON pretty printing with raw text toggle.
* **Response Headers Table**: Clean, searchable inspection of all returned response headers.
* **One-Click Copy**: Instant clipboard copy with visual confirmation.

### 3. Authentication Management
* **Flexible Authentication Modes**:
  * **No Auth**
  * **Bearer Token** (`Authorization: Bearer <token>`)
  * **Basic Auth** (`Authorization: Basic base64(user:pass)`)
  * **API Key** (Injectable via custom Header or URL Query Parameter)
* **Secret Protection**: Password input masking in the browser.

### 4. Collection Management & Portability
* Organize API endpoints into logical collections.
* Save, update, rename, and delete requests.
* **JSON Import & Export**: One-click export and import of entire request collections.
* User isolation: Users only see and modify their own collections and requests.

### 5. Multi-Profile Environment Variables
* Configure custom environments (e.g., *Development*, *Staging*, *Production*).
* Define variables (e.g., `base_url`, `api_token`).
* Dynamic double-curly substitution (`{{base_url}}/users`, `{{api_token}}`) across URLs, headers, parameters, and bodies.
* Sensitive value masking toggle in the environment manager interface.

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
* **User Authentication**: Secure registration, login, session persistence, and password hashing (`password_hash` with `PASSWORD_DEFAULT`).
* **Session Security**: Session ID regeneration (`session_regenerate_id(true)`) upon authentication.
* **CSRF Protection**: Synchronizer token pattern with cryptographically secure tokens (`X-CSRF-Token`) validated on all state-changing endpoints.
* **SSRF Defense**: Outbound URL validator blocking local loopback (`127.0.0.1`), link-local metadata endpoints (`169.254.169.254`), and private RFC1918 subnets, with configurable `ALLOW_LOCAL_REQUESTS` flag for local development.
* **SQL Injection Prevention**: 100% PDO prepared statements.
* **XSS Defense**: Strict UTF-8 escaping and input sanitization.

---

## 🛠️ Free Technology Policy

DevDock was built adhering strictly to a **₹0 budget and zero paid dependencies** standard:

| Layer | Technology | Cost |
| :--- | :--- | :--- |
| **Backend** | Native PHP 8.x + cURL Engine | Free / Open-Source |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ (PDO) | Free / Open-Source |
| **Frontend** | Semantic HTML5, CSS3 Variables, Vanilla ES6+ | Free / Zero Frameworks |
| **Local Dev** | XAMPP / Apache Virtual Host | Free |
| **Live Hosting** | InfinityFree Shared Hosting | Free Forever |
| **Version Control** | Git & GitHub | Free |

---

## 📂 Project Architecture

```
devdock/
├── app/
│   ├── Controllers/
│   │   ├── ApiController.php         # Request execution, collections, history, mock
│   │   └── AuthController.php        # Registration, login, logout, session
│   ├── Core/
│   │   ├── Autoloader.php            # Custom PSR-4 class loader
│   │   ├── Database.php              # PDO Singleton connection manager
│   │   ├── Env.php                   # Environment file (.env) parser
│   │   ├── Request.php               # HTTP request abstraction
│   │   ├── Response.php              # Standardized JSON response emitter
│   │   ├── Router.php                # RESTful regex route dispatcher
│   │   └── Session.php               # Secure session & CSRF management
│   ├── Helpers/
│   │   └── Security.php              # SSRF defense, XSS escaping, email sanitization
│   ├── Services/
│   │   ├── AuthService.php           # User credentials, hashing, registration
│   │   ├── CollectionService.php     # Collection & request persistence
│   │   ├── EnvironmentService.php    # Env variable resolution & substitution
│   │   ├── HistoryService.php        # Audit logging of sent requests
│   │   ├── HttpClientService.php     # PHP cURL outbound request engine
│   │   └── TestRunnerService.php     # Assertion evaluation engine
│   └── Views/
│       └── app.php                   # Single Page Application (SPA) view
├── config/
│   ├── app.php                       # Application configuration
│   ├── database.php                  # Database connection credentials
│   └── database.prod.php.example     # Production database credentials template
├── database/
│   ├── schema.sql                    # Production DDL for phpMyAdmin import
│   ├── export_production.sql         # Clean DDL without CREATE DATABASE
│   └── seed.php                      # CLI database seeder
├── docs/                             # Comprehensive technical documentation
│   ├── 01-REQUIREMENTS-ANALYSIS.md
│   ├── 02-SYSTEM-ARCHITECTURE.md
│   ├── 03-DATABASE-DESIGN.md
│   ├── 04-INSTALLATION-AND-USER-GUIDE.md
│   ├── 05-INFINITYFREE-DEPLOYMENT-GUIDE.md
│   └── 06-FREE-HOSTING-CHECKLIST.md
├── public/
│   ├── assets/
│   │   ├── css/style.css             # Responsive theme styling (Dark/Light)
│   │   └── js/app.js                 # Frontend application logic & state
│   ├── .htaccess                     # Apache rewrite rules
│   └── index.php                     # Front controller
├── storage/
│   ├── cache/                        # Cached responses
│   └── logs/                         # Application error logs
├── .env.example                      # Sample environment variables
├── .gitignore                        # Git exclusion rules
├── .htaccess                         # Root URL routing to public/
├── DEPLOYMENT.md                     # Deployment guide for InfinityFree
├── index.php                         # Root proxy to public/index.php
└── README.md                         # Project documentation
```

---

## 🚀 Quick Start (Local Development)

### Prerequisites
* [XAMPP](https://www.apachefriends.org/) (PHP 8.1+ and MySQL)
* Git

### Step-by-Step Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/Mihir290545/API-Tool.git
   cd API-Tool
   ```

2. **Configure Local Environment**:
   ```bash
   cp .env.example .env
   ```
   Verify local database settings in `.env`:
   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=devdock_db
   DB_USERNAME=root
   DB_PASSWORD=
   ALLOW_LOCAL_REQUESTS=true
   ```

3. **Import Database Schema**:
   * Open phpMyAdmin (`http://localhost/phpmyadmin`).
   * Create database named `devdock_db`.
   * Import `database/schema.sql`.
   * *(Optional)* Run seeder:
     ```bash
     php database/seed.php
     ```

4. **Launch Application**:
   * Symlink or place folder inside `C:\xampp\htdocs\api-tool`.
   * Open your browser: `http://localhost/api-tool/`

---

## 🌐 Production Deployment (InfinityFree)

1. **Database Setup**:
   * Create a MySQL database in the InfinityFree Control Panel (vPanel).
   * Note Hostname (e.g., `sql206.infinityfree.com`), Database Name, Username, and Password.
   * Open phpMyAdmin on InfinityFree and import `database/schema.sql`.

2. **File Upload**:
   * Upload all repository files into `htdocs/`.
   * Create `config/database.prod.php` using the template in `config/database.prod.php.example` with your production database credentials.

3. **Security Check**:
   * Verify that `.env` and `config/database.prod.php` are protected by `.htaccess`.
   * Ensure `ALLOW_LOCAL_REQUESTS=false` in production.

For detailed instructions, refer to [DEPLOYMENT.md](DEPLOYMENT.md).

---

## 📄 License

This project is open-source and released under the [MIT License](LICENSE).
