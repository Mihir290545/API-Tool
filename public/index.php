<?php
/**
 * DevDock — Main Application Front Controller
 */

declare(strict_types=1);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Autoload Core Classes
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', __DIR__ . '/../app');

// Load Environment Configuration
\App\Core\Env::load(__DIR__ . '/../.env');

// Start Secure Session
\App\Core\Session::start();

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\ApiController;

$router = new Router();
$request = new Request();

// -----------------------------------------------------------------------------
// Authentication Endpoints
// -----------------------------------------------------------------------------
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me', [AuthController::class, 'me']);

// -----------------------------------------------------------------------------
// Core Request Execution Endpoint
// -----------------------------------------------------------------------------
$router->post('/api/request/send', [ApiController::class, 'sendRequest']);

// -----------------------------------------------------------------------------
// Collections & Saved Requests Endpoints
// -----------------------------------------------------------------------------
$router->get('/api/collections', [ApiController::class, 'getCollections']);
$router->post('/api/collections', [ApiController::class, 'createCollection']);
$router->put('/api/collections/{id}', [ApiController::class, 'updateCollection']);
$router->delete('/api/collections/{id}', [ApiController::class, 'deleteCollection']);

$router->post('/api/requests', [ApiController::class, 'saveRequest']);
$router->get('/api/requests/{id}', [ApiController::class, 'getRequest']);
$router->delete('/api/requests/{id}', [ApiController::class, 'deleteRequest']);

// -----------------------------------------------------------------------------
// Environments & Variables Endpoints
// -----------------------------------------------------------------------------
$router->get('/api/environments', [ApiController::class, 'getEnvironments']);
$router->get('/api/environments/{id}', [ApiController::class, 'getEnvironment']);
$router->post('/api/environments', [ApiController::class, 'createEnvironment']);
$router->put('/api/environments/{id}', [ApiController::class, 'updateEnvironment']);
$router->delete('/api/environments/{id}', [ApiController::class, 'deleteEnvironment']);

// -----------------------------------------------------------------------------
// Request History Endpoints
// -----------------------------------------------------------------------------
$router->get('/api/history', [ApiController::class, 'getHistory']);
$router->get('/api/history/{id}', [ApiController::class, 'getHistoryItem']);
$router->delete('/api/history/{id}', [ApiController::class, 'deleteHistoryItem']);
$router->delete('/api/history', [ApiController::class, 'clearHistory']);

// -----------------------------------------------------------------------------
// Built-in Free Mock Test Endpoints
// -----------------------------------------------------------------------------
$router->get('/api/mock/ping', [ApiController::class, 'mockPing']);
$router->post('/api/mock/echo', [ApiController::class, 'mockEcho']);
$router->put('/api/mock/echo', [ApiController::class, 'mockEcho']);
$router->delete('/api/mock/echo', [ApiController::class, 'mockEcho']);

// -----------------------------------------------------------------------------
// Frontend SPA View
// -----------------------------------------------------------------------------
$router->get('/', function () {
    require __DIR__ . '/../app/Views/app.php';
});

// Dispatch request
$router->dispatch($request);
