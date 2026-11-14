<?php

namespace App\Core;

/**
 * HTTP Request Handler
 * Normalizes input, JSON payloads, headers, and query parameters.
 */
class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $body;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Normalize URI
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Remove base path if served in subdirectory (e.g. /api-tool/public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        }

        $this->uri = '/' . trim($path, '/');
        $this->queryParams = $_GET;
        $this->headers = $this->captureHeaders();
        $this->body = $this->parseBody();
    }

    private function captureHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerName = str_replace('_', '-', strtolower($key));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    private function parseBody(): array
    {
        $contentType = $this->getHeader('content-type') ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($this->method === 'POST') {
            return $_POST;
        }

        // For PUT/PATCH/DELETE form-urlencoded
        if (in_array($this->method, ['PUT', 'PATCH', 'DELETE'])) {
            $raw = file_get_contents('php://input');
            parse_str($raw, $parsed);
            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQuery(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function getRawBody(): string
    {
        return file_get_contents('php://input');
    }

    public function getHeader(string $name, mixed $default = null): mixed
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
