<?php

namespace App\Core;

/**
 * Lightweight REST Router
 * Handles URI dispatching, path parameter extraction, and middleware execution.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function patch(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middlewares): void
    {
        // Convert route pattern with {param} to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $trimmed = rtrim($pattern, '/');
        $pattern = "#^" . ($trimmed === '' ? '/' : $trimmed) . "$#";

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): void
    {
        $requestMethod = $request->getMethod();
        $requestUri = rtrim($request->getUri(), '/');
        if (empty($requestUri)) {
            $requestUri = '/';
        }

        $allowedMethods = [];
        $matchedRoute = null;
        $matches = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $requestUri, $params)) {
                $allowedMethods[] = $route['method'];
                if ($route['method'] === $requestMethod) {
                    $matchedRoute = $route;
                    // Filter numeric indexes from regex matches
                    $matches = array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY);
                    break;
                }
            }
        }

        if ($matchedRoute) {
            // Execute middlewares
            foreach ($matchedRoute['middlewares'] as $middleware) {
                if (is_callable($middleware)) {
                    $middleware($request);
                } elseif (class_exists($middleware)) {
                    $instance = new $middleware();
                    $instance->handle($request);
                }
            }

            // Execute handler
            $handler = $matchedRoute['handler'];
            if (is_array($handler)) {
                [$controllerClass, $actionMethod] = $handler;
                $controller = new $controllerClass();
                $controller->$actionMethod($request, $matches);
            } elseif (is_callable($handler)) {
                call_user_func($handler, $request, $matches);
            }
            return;
        }

        // Method Not Allowed
        if (!empty($allowedMethods)) {
            Response::error("Method {$requestMethod} not allowed for this route.", 405);
            return;
        }

        // Not Found
        Response::error("Endpoint '{$requestUri}' not found.", 404);
    }
}
