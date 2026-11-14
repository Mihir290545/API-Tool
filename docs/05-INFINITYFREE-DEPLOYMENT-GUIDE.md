# DevDock — InfinityFree Deployment & Hosting Specification

---

## 1. Hosting Architecture on InfinityFree

InfinityFree provides a free cPanel-style hosting environment powered by Apache, PHP 8.2, and a remote MariaDB/MySQL cluster.

### Directory Mapping
```
InfinityFree Account Root
│
├── /htdocs/                        <-- Public Document Root
│   ├── .htaccess                   <-- Root routing & security firewall
│   ├── index.php                   <-- Root proxy entry point
│   ├── app/                        <-- Application code (Protected by 403)
│   ├── config/                     <-- Configuration (database.prod.php protected)
│   ├── database/                   <-- Schema exports
│   ├── public/                     <-- Public assets
│   │   ├── assets/
│   │   │   ├── css/style.css
│   │   │   └── js/app.js
│   │   ├── .htaccess
│   │   └── index.php
│   └── storage/                    <-- Application logs & cache (Protected)
```

---

## 2. Security Firewall Rules (`.htaccess`)
Because shared hosting accounts typically place all files under `htdocs/`, DevDock enforces an Apache security barrier at the root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(app|config|database|storage|docs)/ - [F,L]
    RewriteRule ^(\.env|\.git|\.gitignore|composer\.(json|lock)|README\.md) - [F,L]
    RewriteCond %{REQUEST_URI} ^/public/ [NC]
    RewriteRule ^ - [L]
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```
Any attempt by a browser or crawler to view `.env`, `config/database.prod.php`, or source files in `app/` is met with an immediate `403 Forbidden` response.
