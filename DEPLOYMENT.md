# DevDock — Free Live Deployment Guide (InfinityFree / Free cPanel)

This guide provides step-by-step instructions to deploy **DevDock** to free live hosting without spending any money (₹0 budget).

---

## 1. Free Hosting Compatibility Overview

| Component | DevDock Requirement | InfinityFree Free Hosting Status |
| :--- | :--- | :--- |
| **PHP Version** | PHP 8.1+ / 8.2 | ✅ Supported (Select PHP 8.2 in vPanel) |
| **MySQL / MariaDB** | MySQL 5.7+ / MariaDB 10.4+ | ✅ Supported (Dedicated MySQL database cluster) |
| **PHP PDO Extension** | `pdo_mysql` | ✅ Enabled by default |
| **PHP cURL Extension** | `curl_exec` for outbound HTTP/S | ✅ Supported for standard ports (`80` & `443`) |
| **Apache URL Rewriting**| `mod_rewrite` via `.htaccess` | ✅ Supported |
| **Budget / Costs** | ₹0 / Zero paid services | ✅ 100% Free (No credit card required) |

---

## 2. Step 1: Set Up Your Free InfinityFree Account

1. Go to [https://www.infinityfree.com](https://www.infinityfree.com) and register a free account.
2. In the Client Area, click **Create Account**.
3. Choose a free subdomain (e.g., `devdock.free.nf` or `devdock.infinityfreeapp.com`).
4. Wait 1–2 minutes for the account creation to complete.

---

## 3. Step 2: Create Your Production MySQL Database

1. In your InfinityFree account dashboard, open the **Control Panel (vPanel)**.
2. Under the **Databases** section, click **MySQL Databases**.
3. In the **Create Database** field, enter: `devdock`
4. Click **Create Database**.
5. Note your database credentials displayed on the page:
   * **MySQL Hostname:** e.g., `sql100.infinityfree.com` or `sql200.epizy.com` *(Do NOT use localhost!)*
   * **Database Name:** e.g., `epiz_12345678_devdock`
   * **MySQL Username:** e.g., `epiz_12345678`
   * **MySQL Password:** Your InfinityFree vPanel password

---

## 4. Step 3: Import Database Schema via phpMyAdmin

1. In the vPanel **MySQL Databases** page, click the **phpMyAdmin** button next to your new database.
2. In phpMyAdmin, click your database name in the left sidebar (`epiz_XXXXXXX_devdock`).
3. Click the **Import** tab in the top navigation bar.
4. Click **Choose File** and select:
   `database/export_production.sql`
5. Click **Go** at the bottom.
6. All 10 tables will be created and the initial demo account (`developer@devdock.local`) will be populated automatically!

---

## 5. Step 4: Configure Production Database Credentials

On your local machine before uploading (or directly in the File Manager after uploading):

1. Locate `config/database.prod.php.example` in the project.
2. Create a copy of it and name it:
   `config/database.prod.php`
3. Edit `config/database.prod.php` and insert your actual InfinityFree credentials from Step 2:
   ```php
   <?php
   return [
       'driver' => 'mysql',
       'host' => 'sql100.infinityfree.com',     // Your InfinityFree MySQL Hostname
       'port' => 3306,
       'database' => 'epiz_12345678_devdock',   // Your full database name
       'username' => 'epiz_12345678',           // Your InfinityFree username
       'password' => 'YOUR_VPANEL_PASSWORD',    // Your vPanel password
       'charset' => 'utf8mb4',
       'collation' => 'utf8mb4_unicode_ci',
       'options' => [
           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
           PDO::ATTR_EMULATE_PREPARES => false,
           PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
       ],
   ];
   ```
   *(Note: `database.prod.php` is protected by `.gitignore` and `.htaccess`, so your live password will never be leaked or committed to GitHub).*

---

## 6. Step 5: Upload Files to InfinityFree

You have two upload options:

### Option A: Using Web File Manager (Monsta FTP)
1. In your InfinityFree dashboard, click **File Manager**.
2. Navigate into the **`htdocs/`** directory.
3. Delete any default placeholder files (e.g., `DO NOT UPLOAD FILES HERE` or default `index2.html`).
4. Upload all files and folders of the project into `htdocs/`.
   * Ensure `app/`, `config/`, `public/`, `database/`, `storage/`, `.htaccess`, and `index.php` are uploaded.

### Option B: Using FileZilla (Recommended for Fast Upload)
1. Download [FileZilla Client](https://filezilla-project.org/) (Free).
2. Connect using the FTP details shown in your InfinityFree dashboard:
   * **Host:** `ftpupload.net`
   * **Username:** `epiz_12345678`
   * **Password:** Your vPanel password
   * **Port:** `21`
3. In FileZilla, navigate to the remote directory `/htdocs/`.
4. Upload the project files into `/htdocs/`.

---

## 7. Step 6: Verify Live Deployment

1. Visit your live domain in your browser:
   `https://yourdomain.free.nf/`
2. You should see the DevDock Dark/Light API Studio interface.
3. Click **Send** to verify:
   * cURL outbound request executes.
   * Status code `200 OK` appears with latency and size badges.
   * JSON pretty-printing renders.
   * Automated assertions pass.
   * Request history item is logged.
4. Test security protection:
   Visit `https://yourdomain.free.nf/config/database.php` or `https://yourdomain.free.nf/.env`.
   You will receive **403 Forbidden**, proving that private files are protected.

---

## 8. Summary of Free Hosting Limitations to Keep in Mind

1. **Outbound Ports**: External APIs must use standard ports (`80` for HTTP or `443` for HTTPS). Ports like `:8080` or `:3000` are blocked by InfinityFree's firewall.
2. **Execution Time**: The PHP maximum execution time is capped at 20–30 seconds.
3. **Incoming API Restriction**: InfinityFree uses an anti-bot cookie verification challenge for *incoming* web traffic, which means external applications cannot call endpoints hosted on InfinityFree. Outbound requests from DevDock to external APIs work normally.
