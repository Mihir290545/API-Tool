<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', __DIR__ . '/../app');

\App\Core\Env::load(__DIR__ . '/../.env');

use App\Services\AuthService;

$auth = new AuthService();

try {
    $user = $auth->register('Demo Developer', 'developer@devdock.local', 'password123');
    echo "Successfully seeded default user:\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Password: password123\n";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
