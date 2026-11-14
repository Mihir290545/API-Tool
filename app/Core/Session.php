<?php

namespace App\Core;

/**
 * Secure Session Manager & CSRF Protection
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure cookie parameters
            session_set_cookie_params([
                'lifetime' => 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Initialize CSRF token if not exists
        if (empty($_SESSION['csrf_token'])) {
            self::regenerateCsrfToken();
        }
    }

    public static function regenerateCsrfToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            self::regenerateCsrfToken();
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function setAuthUser(array $user): void
    {
        // Don't store password hash in session
        unset($user['password_hash']);
        $_SESSION['auth_user'] = $user;
    }

    public static function getAuthUser(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    public static function getUserId(): ?int
    {
        return isset($_SESSION['auth_user']['id']) ? (int)$_SESSION['auth_user']['id'] : null;
    }

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['auth_user']['id']);
    }
}
