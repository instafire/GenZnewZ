<?php
/**
 * Health check for GenZNewz - CLI diagnostic.
 *
 * Run it from the project root:
 *
 *     php scripts/health-check.php
 *
 * This used to sit in the web root and be served to anyone who asked. It
 * bootstraps the whole application and echoes raw exception messages, so it
 * was an unauthenticated window into the app (and it never worked from there
 * anyway - its relative paths resolved one directory too high and it answered
 * 500 to every request). It now lives outside public/ and is correct.
 *
 * For a public liveness probe use GET /api/v1/automation/status, which reports
 * the real quality gates and is safe to expose.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');

$checks = [
    'timestamp' => date('Y-m-d H:i:s'),
    'website_url' => 'https://genznewz.com',
];

// Check 1: HTTP Response
$ch = curl_init('https://genznewz.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$checks['http_status'] = [
    'code' => $http_code,
    'healthy' => $http_code === 200,
];

// Check 2: Database Connection
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    \DB::connection()->getPdo();
    $checks['database'] = ['status' => 'connected', 'healthy' => true];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'error', 'message' => $e->getMessage(), 'healthy' => false];
}

// Check 3: Cache Writable
$cache_dir = __DIR__ . '/../storage/framework/cache';
$checks['cache_writable'] = [
    'status' => is_writable($cache_dir) ? 'writable' : 'not_writable',
    'healthy' => is_writable($cache_dir),
];

// Check 4: Storage Permissions
$storage_dirs = [
    'storage/framework/cache' => is_writable(__DIR__ . '/../storage/framework/cache'),
    'storage/framework/sessions' => is_writable(__DIR__ . '/../storage/framework/sessions'),
    'storage/framework/views' => is_writable(__DIR__ . '/../storage/framework/views'),
    'storage/logs' => is_writable(__DIR__ . '/../storage/logs'),
];
$all_writable = !in_array(false, $storage_dirs, true);
$checks['storage_permissions'] = [
    'directories' => $storage_dirs,
    'healthy' => $all_writable,
];

// Overall Health
$checks['overall_health'] = $checks['http_status']['healthy'] && 
                             $checks['database']['healthy'] && 
                             $checks['cache_writable']['healthy'] &&
                             $checks['storage_permissions']['healthy'];

// HTTP Status Code
http_response_code($checks['overall_health'] ? 200 : 503);

echo json_encode($checks, JSON_PRETTY_PRINT);
