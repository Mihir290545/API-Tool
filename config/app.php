<?php
/**
 * DevDock Application Configuration
 */

$isHttps = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

$host = $_SERVER['HTTP_HOST'] ?? 'devdock.freedev.app';
$defaultUrl = ($isHttps ? 'https://' : 'http://') . $host;

return [
    'name' => $_ENV['APP_NAME'] ?? 'DevDock',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($_ENV['APP_URL'] ?? $defaultUrl, '/'),
    
    // HTTP Request Engine limits
    'curl' => [
        'timeout' => (int)($_ENV['CURL_TIMEOUT'] ?? 30),
        'connect_timeout' => (int)($_ENV['CURL_CONNECT_TIMEOUT'] ?? 10),
        'max_body_size' => (int)($_ENV['CURL_MAX_BODY_SIZE'] ?? 5242880), // 5MB
    ],
];
