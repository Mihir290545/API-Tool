<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Helpers\Security;
use PDO;
use Exception;

/**
 * Authentication Service
 * Manages user registration, credential verification, and sessions.
 */
class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function register(string $name, string $email, string $password): array
    {
        $cleanName = Security::sanitizeString($name);
        $cleanEmail = Security::validateEmail($email);

        if (empty($cleanName) || strlen($cleanName) < 2) {
            throw new Exception("Full name must be at least 2 characters long.");
        }

        if (!$cleanEmail) {
            throw new Exception("A valid email address is required.");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        // Check if email already registered
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $cleanEmail]);
        if ($stmt->fetch()) {
            throw new Exception("An account with this email address already exists.");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, created_at)
            VALUES (:name, :email, :password_hash, NOW())
        ");
        $stmt->execute([
            ':name' => $cleanName,
            ':email' => $cleanEmail,
            ':password_hash' => $passwordHash,
        ]);

        $userId = (int)$this->db->lastInsertId();

        // Create default collection and default environment for new user
        $this->createDefaultUserData($userId);

        $user = [
            'id' => $userId,
            'name' => $cleanName,
            'email' => $cleanEmail,
        ];

        Session::setAuthUser($user);

        return $user;
    }

    public function login(string $email, string $password): array
    {
        $cleanEmail = Security::validateEmail($email);
        if (!$cleanEmail) {
            throw new Exception("Invalid email or password.");
        }

        $stmt = $this->db->prepare("SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $cleanEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid email or password.");
        }

        $userData = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        Session::setAuthUser($userData);

        return $userData;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function getCurrentUser(): ?array
    {
        return Session::getAuthUser();
    }

    private function createDefaultUserData(int $userId): void
    {
        // 1. Create a "General APIs" collection
        $stmt = $this->db->prepare("
            INSERT INTO collections (user_id, name, description)
            VALUES (:user_id, 'My First Collection', 'Default collection for testing APIs')
        ");
        $stmt->execute([':user_id' => $userId]);
        $collectionId = (int)$this->db->lastInsertId();

        // 2. Create sample GET request
        $stmt = $this->db->prepare("
            INSERT INTO api_requests (collection_id, user_id, name, method, url, auth_type, body_type)
            VALUES (:collection_id, :user_id, 'Sample JSONPlaceholder Users', 'GET', 'https://jsonplaceholder.typicode.com/users', 'none', 'none')
        ");
        $stmt->execute([
            ':collection_id' => $collectionId,
            ':user_id' => $userId,
        ]);

        // 3. Create default Environment
        $stmt = $this->db->prepare("
            INSERT INTO environments (user_id, name)
            VALUES (:user_id, 'Production')
        ");
        $stmt->execute([':user_id' => $userId]);
        $envId = (int)$this->db->lastInsertId();

        // 4. Add sample variable
        $stmt = $this->db->prepare("
            INSERT INTO environment_variables (environment_id, var_key, var_value, is_secret)
            VALUES (:env_id, 'base_url', 'https://jsonplaceholder.typicode.com', 0)
        ");
        $stmt->execute([':env_id' => $envId]);
    }
}
