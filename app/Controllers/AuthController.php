<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use Exception;

/**
 * Authentication Controller
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register(Request $request): void
    {
        $name = (string)$request->getBody('name', '');
        $email = (string)$request->getBody('email', '');
        $password = (string)$request->getBody('password', '');

        try {
            $user = $this->authService->register($name, $email, $password);
            Response::success([
                'user' => $user,
                'csrf_token' => Session::getCsrfToken(),
            ], 'Registration successful!', 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function login(Request $request): void
    {
        $email = (string)$request->getBody('email', '');
        $password = (string)$request->getBody('password', '');

        try {
            $user = $this->authService->login($email, $password);
            Response::success([
                'user' => $user,
                'csrf_token' => Session::getCsrfToken(),
            ], 'Login successful!');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    public function logout(): void
    {
        $this->authService->logout();
        Response::success(null, 'Logged out successfully.');
    }

    public function me(): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            Response::error('Unauthenticated', 401);
            return;
        }

        Response::success([
            'user' => $user,
            'csrf_token' => Session::getCsrfToken(),
        ]);
    }
}
