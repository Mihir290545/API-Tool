<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Environment & Variable Resolution Service
 */
class EnvironmentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listEnvironments(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.id, e.name, e.created_at, COUNT(v.id) AS variable_count
            FROM environments e
            LEFT JOIN environment_variables v ON e.id = v.environment_id
            WHERE e.user_id = :user_id
            GROUP BY e.id
            ORDER BY e.id ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getEnvironment(int $userId, int $envId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM environments WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute([':id' => $envId, ':user_id' => $userId]);
        $env = $stmt->fetch();

        if (!$env) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id, var_key, var_value, is_secret, is_enabled
            FROM environment_variables
            WHERE environment_id = :env_id
            ORDER BY id ASC
        ");
        $stmt->execute([':env_id' => $envId]);
        $env['variables'] = $stmt->fetchAll();

        return $env;
    }

    public function createEnvironment(int $userId, string $name, array $variables = []): array
    {
        $name = trim($name);
        if (empty($name)) {
            throw new Exception("Environment name cannot be empty.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO environments (user_id, name) VALUES (:user_id, :name)");
            $stmt->execute([':user_id' => $userId, ':name' => $name]);
            $envId = (int)$this->db->lastInsertId();

            if (!empty($variables)) {
                $stmtVar = $this->db->prepare("
                    INSERT INTO environment_variables (environment_id, var_key, var_value, is_secret, is_enabled)
                    VALUES (:env_id, :key, :value, :is_secret, :is_enabled)
                ");

                foreach ($variables as $v) {
                    $key = trim($v['key'] ?? $v['var_key'] ?? '');
                    if (!empty($key)) {
                        $stmtVar->execute([
                            ':env_id' => $envId,
                            ':key' => $key,
                            ':value' => (string)($v['value'] ?? $v['var_value'] ?? ''),
                            ':is_secret' => !empty($v['is_secret']) ? 1 : 0,
                            ':is_enabled' => isset($v['is_enabled']) ? (int)(bool)$v['is_enabled'] : 1,
                        ]);
                    }
                }
            }

            $this->db->commit();
            return $this->getEnvironment($userId, $envId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateEnvironment(int $userId, int $envId, string $name, array $variables): array
    {
        $name = trim($name);
        if (empty($name)) {
            throw new Exception("Environment name cannot be empty.");
        }

        // Verify ownership
        $stmt = $this->db->prepare("SELECT id FROM environments WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute([':id' => $envId, ':user_id' => $userId]);
        if (!$stmt->fetch()) {
            throw new Exception("Environment not found or permission denied.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE environments SET name = :name, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $envId]);

            // Sync variables: delete and re-insert
            $stmt = $this->db->prepare("DELETE FROM environment_variables WHERE environment_id = :env_id");
            $stmt->execute([':env_id' => $envId]);

            $stmtVar = $this->db->prepare("
                INSERT INTO environment_variables (environment_id, var_key, var_value, is_secret, is_enabled)
                VALUES (:env_id, :key, :value, :is_secret, :is_enabled)
            ");

            foreach ($variables as $v) {
                $key = trim($v['key'] ?? $v['var_key'] ?? '');
                if (!empty($key)) {
                    $stmtVar->execute([
                        ':env_id' => $envId,
                        ':key' => $key,
                        ':value' => (string)($v['value'] ?? $v['var_value'] ?? ''),
                        ':is_secret' => !empty($v['is_secret']) ? 1 : 0,
                        ':is_enabled' => isset($v['is_enabled']) ? (int)(bool)$v['is_enabled'] : 1,
                    ]);
                }
            }

            $this->db->commit();
            return $this->getEnvironment($userId, $envId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteEnvironment(int $userId, int $envId): void
    {
        $stmt = $this->db->prepare("DELETE FROM environments WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $envId, ':user_id' => $userId]);
    }

    /**
     * Resolve {{variables}} inside target string using active environment variables
     */
    public function substituteVariables(string $input, array $variables): string
    {
        if (empty($input) || empty($variables)) {
            return $input;
        }

        return preg_replace_callback('/\{\{([a-zA-Z0-9_-]+)\}\}/', function ($matches) use ($variables) {
            $varName = $matches[1];
            return $variables[$varName] ?? $matches[0];
        }, $input);
    }
}
