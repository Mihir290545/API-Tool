<?php
/**
 * DevDock Application Configuration
 */

return [
    'name' => $_ENV['APP_NAME'] ?? 'DevDock',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost/api-tool/public', '/'),
    
    // HTTP Request Engine limits
    'curl' => [
        'timeout' => (int)($_ENV['CURL_TIMEOUT'] ?? 30),
        'connect_timeout' => (int)($_ENV['CURL_CONNECT_TIMEOUT'] ?? 10),
        'max_body_size' => (int)($_ENV['CURL_MAX_BODY_SIZE'] ?? 5242880), // 5MB
    ],
];
