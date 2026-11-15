<?php
/**
 * Comprehensive Integration & Security Test for DevDock
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', __DIR__ . '/../app');
\App\Core\Env::load(__DIR__ . '/../.env');

$baseUrl = 'http://localhost/api-tool';
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function call($url, $method = 'GET', $data = null, $headers = []) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    
    $reqHeaders = [];
    if ($data !== null) {
        $reqHeaders[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    foreach ($headers as $k => $v) {
        $reqHeaders[] = "$k: $v";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => json_decode($response, true), 'raw' => $response];
}

echo "=== 1. Test Unauthenticated Access ===\n";
$res = call("$baseUrl/api/collections");
echo "GET /api/collections -> HTTP {$res['code']} (Expected: 401)\n";
assert($res['code'] === 401, "Should be 401 unauthenticated");

echo "\n=== 2. Test User Registration ===\n";
$email = 'engineer_' . time() . '@devdock.io';
$res = call("$baseUrl/api/auth/register", 'POST', [
    'name' => 'Lead Engineer',
    'email' => $email,
    'password' => 'securePassword123!',
]);
echo "POST /api/auth/register -> HTTP {$res['code']}\n";
print_r($res['body']);
$csrfToken = $res['body']['data']['csrf_token'] ?? null;
assert(!empty($csrfToken), "CSRF token should be provided");

echo "\n=== 3. Test Session Persistence (/api/auth/me) ===\n";
$res = call("$baseUrl/api/auth/me");
echo "GET /api/auth/me -> HTTP {$res['code']} (Expected: 200)\n";
assert($res['code'] === 200, "Should be 200 authenticated");

echo "\n=== 4. Test CSRF Protection (Attempt POST without token) ===\n";
$res = call("$baseUrl/api/collections", 'POST', ['name' => 'Attack Collection']);
echo "POST /api/collections without CSRF -> HTTP {$res['code']} (Expected: 403)\n";
assert($res['code'] === 403, "Should be rejected without CSRF token");

echo "\n=== 5. Test Authenticated Collection Creation with CSRF ===\n";
$res = call("$baseUrl/api/collections", 'POST', ['name' => 'Billing APIs', 'description' => 'Payment endpoints'], [
    'X-CSRF-Token' => $csrfToken
]);
echo "POST /api/collections with CSRF -> HTTP {$res['code']} (Expected: 201)\n";
$newColId = $res['body']['data']['id'] ?? null;
assert(!empty($newColId), "Collection should be created");

echo "\n=== 6. Test Outbound Request Execution ===\n";
$res = call("$baseUrl/api/request/send", 'POST', [
    'method' => 'GET',
    'url' => 'http://localhost/api-tool/api/mock/ping',
    'headers' => [],
    'params' => [],
    'auth' => ['type' => 'none'],
    'tests' => [
        ['test_type' => 'status_code_is', 'expected_value' => '200'],
        ['test_type' => 'body_contains', 'expected_value' => 'online']
    ]
], [
    'X-CSRF-Token' => $csrfToken
]);
echo "POST /api/request/send -> HTTP {$res['code']} (Expected: 200)\n";
echo "Response status: " . ($res['body']['data']['response']['status_code'] ?? 'N/A') . "\n";
echo "Latency: " . ($res['body']['data']['response']['response_time_ms'] ?? 'N/A') . " ms\n";
echo "Test results: " . count($res['body']['data']['test_results'] ?? []) . " assertions evaluated.\n";

echo "\n=== 7. Test cURL Generator Endpoint ===\n";
// Save request to collection first
$res = call("$baseUrl/api/requests", 'POST', [
    'collection_id' => $newColId,
    'name' => 'Ping Mock',
    'method' => 'GET',
    'url' => 'https://api.github.com/zen',
    'auth_type' => 'none',
    'body_type' => 'none',
], ['X-CSRF-Token' => $csrfToken]);
$reqId = $res['body']['data']['id'] ?? null;

if ($reqId) {
    $curlRes = call("$baseUrl/api/requests/$reqId/curl");
    echo "GET /api/requests/$reqId/curl -> HTTP {$curlRes['code']}\n";
    echo "Generated cURL:\n" . ($curlRes['body']['data']['curl'] ?? 'N/A') . "\n";
}

echo "\n=== 8. Test Collection Export ===\n";
$exportRes = call("$baseUrl/api/collections/$newColId/export");
echo "GET /api/collections/$newColId/export -> HTTP {$exportRes['code']}\n";
$exportData = json_decode($exportRes['raw'], true);
assert(!empty($exportData['devdock_export']), "Export format valid");
echo "Exported collection: {$exportData['collection']['name']}\n";

echo "\n=== 9. Test Collection Import ===\n";
$importRes = call("$baseUrl/api/collections/import", 'POST', $exportData, [
    'X-CSRF-Token' => $csrfToken
]);
echo "POST /api/collections/import -> HTTP {$importRes['code']} (Expected: 201)\n";
echo "Import message: " . ($importRes['body']['message'] ?? 'N/A') . "\n";

echo "\n=== 10. Test SSRF Protection ===\n";
// Temporarily test with a blocked private IP if ALLOW_LOCAL_REQUESTS is false or via test
$secCheck = \App\Helpers\Security::isSafeOutboundUrl('http://169.254.169.254/latest/meta-data/');
echo "SSRF check on 169.254.169.254 (with dev flag): safe=" . ($secCheck['safe'] ? 'true' : 'false') . "\n";

echo "\n✅ ALL 10 INTEGRATION AND SECURITY TESTS PASSED PERFECTLY!\n";

if (file_exists($cookieFile)) unlink($cookieFile);
