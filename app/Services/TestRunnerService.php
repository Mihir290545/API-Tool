<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Automated API Test & Assertion Engine
 */
class TestRunnerService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Run configured tests against HTTP response
     * 
     * @param array $tests List of test definitions
     * @param array $response Standardized HTTP response
     * @param int|null $historyId Request history ID to attach results
     * @return array Evaluated test results
     */
    public function runTests(array $tests, array $response, ?int $historyId = null): array
    {
        $results = [];
        $decodedJson = null;
        $isJson = false;

        $contentType = '';
        foreach ($response['response_headers'] as $headerKey => $headerVal) {
            if (strtolower($headerKey) === 'content-type') {
                $contentType = is_array($headerVal) ? implode(';', $headerVal) : $headerVal;
                break;
            }
        }

        if (str_contains($contentType, 'application/json') || !empty($response['response_body'])) {
            $parsed = json_decode($response['response_body'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $decodedJson = $parsed;
                $isJson = true;
            }
        }

        foreach ($tests as $test) {
            if (isset($test['is_enabled']) && !$test['is_enabled']) {
                continue;
            }

            $type = $test['test_type'] ?? '';
            $expected = trim($test['expected_value'] ?? '');
            $propPath = trim($test['property_path'] ?? '');
            $testId = !empty($test['id']) ? (int)$test['id'] : null;

            $passed = false;
            $actual = '';
            $testName = '';
            $errorMessage = null;

            switch ($type) {
                case 'status_code_is':
                    $testName = "Status code is {$expected}";
                    $actual = (string)($response['status_code'] ?? 0);
                    $passed = ($actual === $expected);
                    if (!$passed) {
                        $errorMessage = "Expected status code {$expected}, but received {$actual}.";
                    }
                    break;

                case 'response_time_less_than':
                    $testName = "Response time is less than {$expected}ms";
                    $actualTime = (float)($response['response_time_ms'] ?? 0);
                    $actual = "{$actualTime}ms";
                    $passed = ($actualTime < (float)$expected);
                    if (!$passed) {
                        $errorMessage = "Expected response time under {$expected}ms, but took {$actualTime}ms.";
                    }
                    break;

                case 'body_contains':
                    $testName = "Response body contains '{$expected}'";
                    $body = (string)($response['response_body'] ?? '');
                    $passed = str_contains($body, $expected);
                    $actual = $passed ? "Found in body" : "Not found";
                    if (!$passed) {
                        $errorMessage = "String '{$expected}' was not present in the response body.";
                    }
                    break;

                case 'json_property_exists':
                    $testName = "JSON property '{$propPath}' exists";
                    if (!$isJson) {
                        $passed = false;
                        $actual = "Not valid JSON";
                        $errorMessage = "Response body is not valid JSON.";
                    } else {
                        $exists = $this->hasNestedProperty($decodedJson, $propPath);
                        $passed = $exists;
                        $actual = $exists ? "Property exists" : "Property not found";
                        if (!$passed) {
                            $errorMessage = "JSON key '{$propPath}' was not found in response payload.";
                        }
                    }
                    break;

                case 'json_property_equals':
                    $testName = "JSON '{$propPath}' equals '{$expected}'";
                    if (!$isJson) {
                        $passed = false;
                        $actual = "Not valid JSON";
                        $errorMessage = "Response body is not valid JSON.";
                    } else {
                        $val = $this->getNestedProperty($decodedJson, $propPath);
                        $actual = is_scalar($val) ? (string)$val : json_encode($val);
                        $passed = ($actual === $expected);
                        if (!$passed) {
                            $errorMessage = "Expected '{$expected}', but received '{$actual}'.";
                        }
                    }
                    break;

                default:
                    $testName = "Custom Assertion";
                    $passed = false;
                    $errorMessage = "Unknown assertion rule: {$type}";
                    break;
            }

            $resultItem = [
                'test_id' => $testId,
                'test_name' => $testName,
                'passed' => $passed,
                'actual_value' => $actual,
                'error_message' => $errorMessage,
            ];

            // Persist to test_results if historyId is present
            if ($historyId) {
                $stmt = $this->db->prepare("
                    INSERT INTO test_results (history_id, test_id, test_name, passed, actual_value, error_message, executed_at)
                    VALUES (:history_id, :test_id, :test_name, :passed, :actual_val, :error_msg, NOW())
                ");
                $stmt->execute([
                    ':history_id' => $historyId,
                    ':test_id' => $testId,
                    ':test_name' => $testName,
                    ':passed' => $passed ? 1 : 0,
                    ':actual_val' => $actual,
                    ':error_msg' => $errorMessage,
                ]);
            }

            $results[] = $resultItem;
        }

        return $results;
    }

    private function getNestedProperty(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $k) {
            if (is_array($current) && array_key_exists($k, $current)) {
                $current = $current[$k];
            } else {
                return null;
            }
        }

        return $current;
    }

    private function hasNestedProperty(array $data, string $path): bool
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $k) {
            if (is_array($current) && array_key_exists($k, $current)) {
                $current = $current[$k];
            } else {
                return false;
            }
        }

        return true;
    }
}
