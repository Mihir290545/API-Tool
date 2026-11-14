<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Collection & Saved Request Management Service
 */
class CollectionService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getCollectionsWithRequests(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, created_at
            FROM collections
            WHERE user_id = :user_id
            ORDER BY name ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        $collections = $stmt->fetchAll();

        foreach ($collections as &$col) {
            $stmtReq = $this->db->prepare("
                SELECT id, collection_id, name, method, url, auth_type, body_type, updated_at
                FROM api_requests
                WHERE collection_id = :collection_id AND user_id = :user_id
                ORDER BY id ASC
            ");
            $stmtReq->execute([
                ':collection_id' => $col['id'],
                ':user_id' => $userId,
            ]);
            $col['requests'] = $stmtReq->fetchAll();
        }

        return $collections;
    }

    public function createCollection(int $userId, string $name, ?string $description = null): array
    {
        $name = trim($name);
        if (empty($name)) {
            throw new Exception("Collection name cannot be empty.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO collections (user_id, name, description, created_at)
            VALUES (:user_id, :name, :description, NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':description' => $description,
        ]);

        return [
            'id' => (int)$this->db->lastInsertId(),
            'name' => $name,
            'description' => $description,
            'requests' => [],
        ];
    }

    public function updateCollection(int $userId, int $collectionId, string $name, ?string $description = null): void
    {
        $name = trim($name);
        if (empty($name)) {
            throw new Exception("Collection name cannot be empty.");
        }

        $stmt = $this->db->prepare("
            UPDATE collections 
            SET name = :name, description = :description, updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':id' => $collectionId,
            ':user_id' => $userId,
        ]);
    }

    public function deleteCollection(int $userId, int $collectionId): void
    {
        $stmt = $this->db->prepare("DELETE FROM collections WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $collectionId, ':user_id' => $userId]);
    }

    public function saveRequest(int $userId, array $data): array
    {
        $name = trim($data['name'] ?? 'Untitled Request');
        $method = strtoupper(trim($data['method'] ?? 'GET'));
        $url = trim($data['url'] ?? '');
        $collectionId = !empty($data['collection_id']) ? (int)$data['collection_id'] : null;
        $requestId = !empty($data['id']) ? (int)$data['id'] : null;
        $authType = $data['auth_type'] ?? 'none';
        $authConfig = !empty($data['auth_config']) ? json_encode($data['auth_config']) : null;
        $bodyType = $data['body_type'] ?? 'none';
        $bodyContent = $data['body_content'] ?? null;

        if (empty($url)) {
            throw new Exception("Request URL is required.");
        }

        $this->db->beginTransaction();
        try {
            if ($requestId) {
                // Update existing request
                $stmt = $this->db->prepare("
                    UPDATE api_requests
                    SET collection_id = :col_id, name = :name, method = :method, url = :url,
                        auth_type = :auth_type, auth_config = :auth_config, body_type = :body_type,
                        body_content = :body_content, updated_at = NOW()
                    WHERE id = :id AND user_id = :user_id
                ");
                $stmt->execute([
                    ':col_id' => $collectionId,
                    ':name' => $name,
                    ':method' => $method,
                    ':url' => $url,
                    ':auth_type' => $authType,
                    ':auth_config' => $authConfig,
                    ':body_type' => $bodyType,
                    ':body_content' => $bodyContent,
                    ':id' => $requestId,
                    ':user_id' => $userId,
                ]);
            } else {
                // Create new request
                $stmt = $this->db->prepare("
                    INSERT INTO api_requests (collection_id, user_id, name, method, url, auth_type, auth_config, body_type, body_content, created_at)
                    VALUES (:col_id, :user_id, :name, :method, :url, :auth_type, :auth_config, :body_type, :body_content, NOW())
                ");
                $stmt->execute([
                    ':col_id' => $collectionId,
                    ':user_id' => $userId,
                    ':name' => $name,
                    ':method' => $method,
                    ':url' => $url,
                    ':auth_type' => $authType,
                    ':auth_config' => $authConfig,
                    ':body_type' => $bodyType,
                    ':body_content' => $bodyContent,
                ]);
                $requestId = (int)$this->db->lastInsertId();
            }

            // Sync Headers
            $stmtDelHeaders = $this->db->prepare("DELETE FROM request_headers WHERE request_id = :req_id");
            $stmtDelHeaders->execute([':req_id' => $requestId]);

            if (!empty($data['headers']) && is_array($data['headers'])) {
                $stmtHeader = $this->db->prepare("
                    INSERT INTO request_headers (request_id, header_name, header_value, is_enabled)
                    VALUES (:req_id, :name, :val, :enabled)
                ");
                foreach ($data['headers'] as $h) {
                    $hName = trim($h['key'] ?? $h['header_name'] ?? '');
                    if (!empty($hName)) {
                        $stmtHeader->execute([
                            ':req_id' => $requestId,
                            ':name' => $hName,
                            ':val' => (string)($h['value'] ?? $h['header_value'] ?? ''),
                            ':enabled' => isset($h['is_enabled']) ? (int)(bool)$h['is_enabled'] : 1,
                        ]);
                    }
                }
            }

            // Sync Params
            $stmtDelParams = $this->db->prepare("DELETE FROM request_params WHERE request_id = :req_id");
            $stmtDelParams->execute([':req_id' => $requestId]);

            if (!empty($data['params']) && is_array($data['params'])) {
                $stmtParam = $this->db->prepare("
                    INSERT INTO request_params (request_id, param_key, param_value, is_enabled)
                    VALUES (:req_id, :key, :val, :enabled)
                ");
                foreach ($data['params'] as $p) {
                    $pKey = trim($p['key'] ?? $p['param_key'] ?? '');
                    if (!empty($pKey)) {
                        $stmtParam->execute([
                            ':req_id' => $requestId,
                            ':key' => $pKey,
                            ':val' => (string)($p['value'] ?? $p['param_value'] ?? ''),
                            ':enabled' => isset($p['is_enabled']) ? (int)(bool)$p['is_enabled'] : 1,
                        ]);
                    }
                }
            }

            // Sync Automated Tests
            $stmtDelTests = $this->db->prepare("DELETE FROM automated_tests WHERE request_id = :req_id");
            $stmtDelTests->execute([':req_id' => $requestId]);

            if (!empty($data['tests']) && is_array($data['tests'])) {
                $stmtTest = $this->db->prepare("
                    INSERT INTO automated_tests (request_id, test_type, expected_value, property_path, is_enabled)
                    VALUES (:req_id, :test_type, :expected, :prop_path, :enabled)
                ");
                foreach ($data['tests'] as $t) {
                    $testType = trim($t['test_type'] ?? '');
                    if (!empty($testType)) {
                        $stmtTest->execute([
                            ':req_id' => $requestId,
                            ':test_type' => $testType,
                            ':expected' => (string)($t['expected_value'] ?? ''),
                            ':prop_path' => !empty($t['property_path']) ? $t['property_path'] : null,
                            ':enabled' => isset($t['is_enabled']) ? (int)(bool)$t['is_enabled'] : 1,
                        ]);
                    }
                }
            }

            $this->db->commit();
            return $this->getRequestDetails($userId, $requestId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getRequestDetails(int $userId, int $requestId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM api_requests WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute([':id' => $requestId, ':user_id' => $userId]);
        $request = $stmt->fetch();

        if (!$request) {
            return null;
        }

        if (!empty($request['auth_config'])) {
            $request['auth_config'] = json_decode($request['auth_config'], true);
        }

        // Fetch headers
        $stmtH = $this->db->prepare("SELECT header_name as `key`, header_value as `value`, is_enabled FROM request_headers WHERE request_id = :id");
        $stmtH->execute([':id' => $requestId]);
        $request['headers'] = $stmtH->fetchAll();

        // Fetch params
        $stmtP = $this->db->prepare("SELECT param_key as `key`, param_value as `value`, is_enabled FROM request_params WHERE request_id = :id");
        $stmtP->execute([':id' => $requestId]);
        $request['params'] = $stmtP->fetchAll();

        // Fetch tests
        $stmtT = $this->db->prepare("SELECT id, test_type, expected_value, property_path, is_enabled FROM automated_tests WHERE request_id = :id");
        $stmtT->execute([':id' => $requestId]);
        $request['tests'] = $stmtT->fetchAll();

        return $request;
    }

    public function deleteRequest(int $userId, int $requestId): void
    {
        $stmt = $this->db->prepare("DELETE FROM api_requests WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $requestId, ':user_id' => $userId]);
    }
}
