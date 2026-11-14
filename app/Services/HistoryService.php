<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Request History Management Service
 */
class HistoryService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function log(int $userId, array $requestData, array $responseData, ?int $requestId = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO request_history (
                user_id, request_id, method, url, request_headers, request_body,
                status_code, status_text, response_headers, response_body,
                response_time_ms, response_size_bytes, error_message, executed_at
            ) VALUES (
                :user_id, :request_id, :method, :url, :req_headers, :req_body,
                :status_code, :status_text, :res_headers, :res_body,
                :res_time, :res_size, :error_msg, NOW()
            )
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':request_id' => $requestId,
            ':method' => strtoupper($requestData['method'] ?? 'GET'),
            ':url' => $requestData['url'] ?? '',
            ':req_headers' => !empty($requestData['headers']) ? json_encode($requestData['headers']) : null,
            ':req_body' => $requestData['body'] ?? null,
            ':status_code' => $responseData['status_code'] ?? null,
            ':status_text' => $responseData['status_text'] ?? null,
            ':res_headers' => !empty($responseData['response_headers']) ? json_encode($responseData['response_headers']) : null,
            ':res_body' => $responseData['response_body'] ?? null,
            ':res_time' => $responseData['response_time_ms'] ?? null,
            ':res_size' => $responseData['response_size_bytes'] ?? null,
            ':error_msg' => $responseData['error_message'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function listHistory(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT id, request_id, method, url, status_code, status_text, response_time_ms, response_size_bytes, executed_at
            FROM request_history
            WHERE user_id = :user_id
            ORDER BY executed_at DESC, id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getHistoryItem(int $userId, int $historyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM request_history
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $historyId, ':user_id' => $userId]);
        $item = $stmt->fetch();

        if (!$item) {
            return null;
        }

        if (!empty($item['request_headers'])) {
            $item['request_headers'] = json_decode($item['request_headers'], true);
        }
        if (!empty($item['response_headers'])) {
            $item['response_headers'] = json_decode($item['response_headers'], true);
        }

        // Fetch test results for this execution if any
        $stmtResults = $this->db->prepare("
            SELECT id, test_name, passed, actual_value, error_message, executed_at
            FROM test_results
            WHERE history_id = :history_id
            ORDER BY id ASC
        ");
        $stmtResults->execute([':history_id' => $historyId]);
        $item['test_results'] = $stmtResults->fetchAll();

        return $item;
    }

    public function deleteHistoryItem(int $userId, int $historyId): void
    {
        $stmt = $this->db->prepare("DELETE FROM request_history WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $historyId, ':user_id' => $userId]);
    }

    public function clearHistory(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM request_history WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    }
}
