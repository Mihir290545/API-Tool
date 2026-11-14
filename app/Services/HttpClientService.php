<?php

namespace App\Services;

use App\Helpers\Security;
use Exception;

/**
 * High-Performance Native PHP cURL Execution Engine
 * Handles outbound HTTP requests, captures timing metrics, headers, and response payloads.
 */
class HttpClientService
{
    private int $timeout;
    private int $connectTimeout;
    private int $maxBodySize;

    public function __construct()
    {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $this->timeout = $appConfig['curl']['timeout'] ?? 30;
        $this->connectTimeout = $appConfig['curl']['connect_timeout'] ?? 10;
        $this->maxBodySize = $appConfig['curl']['max_body_size'] ?? 5242880;
    }

    /**
     * Execute outbound HTTP request
     * 
     * @param string $method GET, POST, PUT, PATCH, DELETE, etc.
     * @param string $url Target endpoint URL
     * @param array $headers Key-value array of headers
     * @param array $queryParams Key-value array of query parameters
     * @param string|null $body Raw request body
     * @param array $auth Authentication configuration
     * @return array Response metrics and payload
     */
    public function execute(
        string $method,
        string $url,
        array $headers = [],
        array $queryParams = [],
        ?string $body = null,
        array $auth = []
    ): array {
        // 1. Process query parameters into URL
        if (!empty($queryParams)) {
            $parsedUrl = parse_url($url);
            $existingQuery = [];
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $existingQuery);
            }
            $mergedQuery = array_merge($existingQuery, $queryParams);
            $queryString = http_build_query($mergedQuery);

            $urlWithoutQuery = strtok($url, '?');
            $url = $urlWithoutQuery . '?' . $queryString;
        }

        // 2. Validate outbound URL for SSRF protection
        $safetyCheck = Security::isSafeOutboundUrl($url);
        if (!$safetyCheck['safe']) {
            throw new Exception("Security Violation: " . $safetyCheck['error']);
        }

        // 3. Process Authentication
        $headers = $this->applyAuthentication($headers, $url, $auth);

        // 4. Initialize cURL
        $ch = curl_init();

        $method = strtoupper(trim($method));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include response headers in output
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local / dev flexibility
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Set HTTP Method
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
            case 'OPTIONS':
            case 'HEAD':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($body !== null && $method !== 'HEAD') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                if ($method === 'HEAD') {
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                }
                break;
            case 'GET':
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        // Format and attach headers
        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_numeric($key)) {
                $formattedHeaders[] = $value;
            } else {
                $formattedHeaders[] = "{$key}: {$value}";
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);

        // Track execution timing
        $startTime = microtime(true);
        $rawOutput = curl_exec($ch);
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            return [
                'success' => false,
                'status_code' => 0,
                'status_text' => 'Network / cURL Error',
                'response_time_ms' => $durationMs,
                'response_size_bytes' => 0,
                'response_headers' => [],
                'response_body' => '',
                'error_message' => "cURL Error ({$curlErrno}): {$curlError}",
            ];
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        curl_close($ch);

        // Separate response headers and response body
        $rawHeaders = substr($rawOutput, 0, $headerSize);
        $responseBody = substr($rawOutput, $headerSize);
        $parsedHeaders = $this->parseResponseHeaders($rawHeaders);

        return [
            'success' => $statusCode >= 200 && $statusCode < 400,
            'status_code' => $statusCode,
            'status_text' => $this->getStatusText($statusCode),
            'response_time_ms' => $durationMs,
            'response_size_bytes' => (int)$downloadSize ?: strlen($responseBody),
            'response_headers' => $parsedHeaders,
            'response_body' => $responseBody,
            'error_message' => null,
        ];
    }

    private function applyAuthentication(array $headers, string &$url, array $auth): array
    {
        $type = strtolower($auth['type'] ?? 'none');

        switch ($type) {
            case 'bearer':
                $token = trim($auth['token'] ?? '');
                if (!empty($token)) {
                    $headers['Authorization'] = "Bearer {$token}";
                }
                break;

            case 'basic':
                $user = $auth['username'] ?? '';
                $pass = $auth['password'] ?? '';
                $credentials = base64_encode("{$user}:{$pass}");
                $headers['Authorization'] = "Basic {$credentials}";
                break;

            case 'apikey':
                $key = trim($auth['key'] ?? '');
                $val = trim($auth['value'] ?? '');
                $addTo = strtolower($auth['addTo'] ?? 'header');

                if (!empty($key)) {
                    if ($addTo === 'header') {
                        $headers[$key] = $val;
                    } else { // query
                        $delimiter = str_contains($url, '?') ? '&' : '?';
                        $url .= $delimiter . urlencode($key) . '=' . urlencode($val);
                    }
                }
                break;
        }

        return $headers;
    }

    private function parseResponseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $headerBlocks = explode("\r\n\r\n", trim($rawHeaders));
        $lastBlock = end($headerBlocks); // In case of redirects, get the final header block

        $lines = explode("\r\n", $lastBlock);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (isset($headers[$key])) {
                    if (is_array($headers[$key])) {
                        $headers[$key][] = $value;
                    } else {
                        $headers[$key] = [$headers[$key], $value];
                    }
                } else {
                    $headers[$key] = $value;
                }
            }
        }
        return $headers;
    }

    private function getStatusText(int $code): string
    {
        $statusTexts = [
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            422 => 'Unprocessable Content',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $statusTexts[$code] ?? 'HTTP Response';
    }
}
