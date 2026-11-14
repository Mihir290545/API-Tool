<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\HttpClientService;
use App\Services\CollectionService;
use App\Services\EnvironmentService;
use App\Services\HistoryService;
use App\Services\TestRunnerService;
use Exception;

/**
 * Main API Controller for DevDock Actions
 */
class ApiController
{
    private HttpClientService $httpClient;
    private CollectionService $collectionService;
    private EnvironmentService $envService;
    private HistoryService $historyService;
    private TestRunnerService $testRunner;

    public function __construct()
    {
        $this->httpClient = new HttpClientService();
        $this->collectionService = new CollectionService();
        $this->envService = new EnvironmentService();
        $this->historyService = new HistoryService();
        $this->testRunner = new TestRunnerService();
    }

    private function getUserId(): int
    {
        $userId = Session::getUserId();
        if (!$userId) {
            // For developer convenience / single-user local mode, default to user 1 if logged in or seeded
            return 1;
        }
        return $userId;
    }

    /**
     * Execute an API request, run tests, and save history
     */
    public function sendRequest(Request $request): void
    {
        $userId = $this->getUserId();

        $method = strtoupper(trim($request->getBody('method', 'GET')));
        $url = trim($request->getBody('url', ''));
        $headers = $request->getBody('headers', []);
        $params = $request->getBody('params', []);
        $auth = $request->getBody('auth', ['type' => 'none']);
        $body = $request->getBody('body', null);
        $tests = $request->getBody('tests', []);
        $envId = $request->getBody('environment_id', null);
        $savedRequestId = $request->getBody('request_id', null);

        if (empty($url)) {
            Response::error("Target URL cannot be empty.", 422);
            return;
        }

        // 1. Resolve environment variables if environment is selected
        $activeVariables = [];
        if ($envId) {
            $env = $this->envService->getEnvironment($userId, (int)$envId);
            if ($env && !empty($env['variables'])) {
                foreach ($env['variables'] as $v) {
                    if ($v['is_enabled']) {
                        $activeVariables[$v['var_key']] = $v['var_value'];
                    }
                }
            }
        }

        // Apply substitution
        $url = $this->envService->substituteVariables($url, $activeVariables);

        // Format headers array
        $parsedHeaders = [];
        if (is_array($headers)) {
            foreach ($headers as $h) {
                $enabled = isset($h['is_enabled']) ? (bool)$h['is_enabled'] : true;
                $key = trim($h['key'] ?? '');
                $val = trim($h['value'] ?? '');
                if ($enabled && !empty($key)) {
                    $val = $this->envService->substituteVariables($val, $activeVariables);
                    $parsedHeaders[$key] = $val;
                }
            }
        }

        // Format query params array
        $parsedParams = [];
        if (is_array($params)) {
            foreach ($params as $p) {
                $enabled = isset($p['is_enabled']) ? (bool)$p['is_enabled'] : true;
                $key = trim($p['key'] ?? '');
                $val = trim($p['value'] ?? '');
                if ($enabled && !empty($key)) {
                    $val = $this->envService->substituteVariables($val, $activeVariables);
                    $parsedParams[$key] = $val;
                }
            }
        }

        // Format body
        if (is_string($body)) {
            $body = $this->envService->substituteVariables($body, $activeVariables);
        }

        // Apply auth variable substitution
        if (!empty($auth['token'])) {
            $auth['token'] = $this->envService->substituteVariables($auth['token'], $activeVariables);
        }
        if (!empty($auth['value'])) {
            $auth['value'] = $this->envService->substituteVariables($auth['value'], $activeVariables);
        }

        try {
            // 2. Execute outbound request via cURL
            $response = $this->httpClient->execute(
                $method,
                $url,
                $parsedHeaders,
                $parsedParams,
                $body,
                $auth
            );

            // 3. Log to history
            $historyId = $this->historyService->log(
                $userId,
                [
                    'method' => $method,
                    'url' => $url,
                    'headers' => $parsedHeaders,
                    'body' => $body,
                ],
                $response,
                $savedRequestId ? (int)$savedRequestId : null
            );

            // 4. Run automated tests / assertions
            $testResults = [];
            if (!empty($tests) && is_array($tests)) {
                $testResults = $this->testRunner->runTests($tests, $response, $historyId);
            }

            Response::success([
                'history_id' => $historyId,
                'response' => $response,
                'test_results' => $testResults,
            ], 'Request executed.');

        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // Collections
    // -------------------------------------------------------------------------
    public function getCollections(): void
    {
        $userId = $this->getUserId();
        $collections = $this->collectionService->getCollectionsWithRequests($userId);
        Response::success($collections);
    }

    public function createCollection(Request $request): void
    {
        $userId = $this->getUserId();
        $name = (string)$request->getBody('name', '');
        $description = (string)$request->getBody('description', '');

        try {
            $collection = $this->collectionService->createCollection($userId, $name, $description);
            Response::success($collection, 'Collection created.', 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function updateCollection(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $colId = (int)($params['id'] ?? 0);
        $name = (string)$request->getBody('name', '');
        $description = (string)$request->getBody('description', '');

        try {
            $this->collectionService->updateCollection($userId, $colId, $name, $description);
            Response::success(null, 'Collection updated.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function deleteCollection(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $colId = (int)($params['id'] ?? 0);
        $this->collectionService->deleteCollection($userId, $colId);
        Response::success(null, 'Collection deleted.');
    }

    public function saveRequest(Request $request): void
    {
        $userId = $this->getUserId();
        $data = $request->getBody();

        try {
            $saved = $this->collectionService->saveRequest($userId, $data);
            Response::success($saved, 'Request saved successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function getRequest(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $reqId = (int)($params['id'] ?? 0);
        $details = $this->collectionService->getRequestDetails($userId, $reqId);

        if (!$details) {
            Response::error('Request not found.', 404);
            return;
        }

        Response::success($details);
    }

    public function deleteRequest(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $reqId = (int)($params['id'] ?? 0);
        $this->collectionService->deleteRequest($userId, $reqId);
        Response::success(null, 'Request deleted.');
    }

    // -------------------------------------------------------------------------
    // Environments
    // -------------------------------------------------------------------------
    public function getEnvironments(): void
    {
        $userId = $this->getUserId();
        $environments = $this->envService->listEnvironments($userId);
        Response::success($environments);
    }

    public function getEnvironment(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $envId = (int)($params['id'] ?? 0);
        $env = $this->envService->getEnvironment($userId, $envId);

        if (!$env) {
            Response::error('Environment not found.', 404);
            return;
        }

        Response::success($env);
    }

    public function createEnvironment(Request $request): void
    {
        $userId = $this->getUserId();
        $name = (string)$request->getBody('name', '');
        $variables = $request->getBody('variables', []);

        try {
            $env = $this->envService->createEnvironment($userId, $name, is_array($variables) ? $variables : []);
            Response::success($env, 'Environment created.', 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function updateEnvironment(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $envId = (int)($params['id'] ?? 0);
        $name = (string)$request->getBody('name', '');
        $variables = $request->getBody('variables', []);

        try {
            $env = $this->envService->updateEnvironment($userId, $envId, $name, is_array($variables) ? $variables : []);
            Response::success($env, 'Environment updated.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function deleteEnvironment(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $envId = (int)($params['id'] ?? 0);
        $this->envService->deleteEnvironment($userId, $envId);
        Response::success(null, 'Environment deleted.');
    }

    // -------------------------------------------------------------------------
    // History
    // -------------------------------------------------------------------------
    public function getHistory(): void
    {
        $userId = $this->getUserId();
        $history = $this->historyService->listHistory($userId);
        Response::success($history);
    }

    public function getHistoryItem(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $historyId = (int)($params['id'] ?? 0);
        $item = $this->historyService->getHistoryItem($userId, $historyId);

        if (!$item) {
            Response::error('History item not found.', 404);
            return;
        }

        Response::success($item);
    }

    public function deleteHistoryItem(Request $request, array $params): void
    {
        $userId = $this->getUserId();
        $historyId = (int)($params['id'] ?? 0);
        $this->historyService->deleteHistoryItem($userId, $historyId);
        Response::success(null, 'History item deleted.');
    }

    public function clearHistory(): void
    {
        $userId = $this->getUserId();
        $this->historyService->clearHistory($userId);
        Response::success(null, 'History cleared.');
    }

    // -------------------------------------------------------------------------
    // Built-in Mock Endpoints (For 100% Free, Offline Testing)
    // -------------------------------------------------------------------------
    public function mockPing(): void
    {
        Response::success([
            'status' => 'online',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => 'DevDock Mock Engine',
        ], 'Pong!');
    }

    public function mockEcho(Request $request): void
    {
        Response::success([
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
            'queryParams' => $request->getQuery(),
            'body' => $request->getBody(),
            'rawBody' => $request->getRawBody(),
            'ip' => $request->getClientIp(),
            'timestamp' => time(),
        ], 'Echo response');
    }
}
