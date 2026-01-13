<?php

/**
 * Security Audit CLI
 * Checks basic security configurations.
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$checks = [];

// 1. Check Display Errors
$displayErrors = ini_get('display_errors');
$env = $_ENV['APP_ENV'] ?? 'production';
$isDev = in_array($env, ['local', 'development', 'dev']);

if ($isDev) {
    $status = 'PASS';
    $msg = "Current: $displayErrors (Allowed in $env)";
} else {
    $status = ($displayErrors == '0' || strtolower($displayErrors) == 'off') ? 'PASS' : 'FAIL';
    $msg = "Current: $displayErrors (Should be Off in Production)";
}

$checks[] = [
    'name' => 'PHP Display Errors',
    'status' => $status,
    'detail' => $msg
];

// 2. Check Directory Write Permissions (Basic)
$criticalDirs = [
    'config' => false, // Should NOT be writable by web server user ideally, but this CLI runs as owner?
    'public' => false,
    'logs' => true,
    'storage' => true
];

foreach ($criticalDirs as $dir => $shouldBeWritable) {
    $path = __DIR__ . '/../' . $dir;
    if (is_dir($path)) {
        $isWritable = is_writable($path);
        $status = ($isWritable === $shouldBeWritable) ? 'PASS' : 'WARN';
        $checks[] = [
            'name' => "Directory Permissions: $dir",
            'status' => $status,
            'detail' => "Writable: " . ($isWritable ? 'Yes' : 'No') . " (Expected: " . ($shouldBeWritable ? 'Yes' : 'No') . ")"
        ];
    }
}

// 3. Database Integrity
$db = \MCAG\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
try {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmt = $db->query("PRAGMA integrity_check");
        $result = $stmt->fetchColumn();
        $checks[] = [
            'name' => 'SQLite Integrity',
            'status' => ($result === 'ok') ? 'PASS' : 'FAIL',
            'detail' => $result
        ];
    } elseif ($driver === 'mysql') {
        // MySQL equivalent check (checking connection and basic table status)
        $stmt = $db->query("SELECT 1"); // Basic ping
        $checks[] = [
            'name' => 'MySQL Connectivity',
            'status' => $stmt ? 'PASS' : 'FAIL',
            'detail' => 'Connection established successfully'
        ];
    } else {
        $checks[] = [
            'name' => 'Database Check',
            'status' => 'WARN',
            'detail' => "Driver verification not implemented for: $driver"
        ];
    }
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Database Integrity',
        'status' => 'FAIL',
        'detail' => $e->getMessage()
    ];
}

// 4. RateLimit Directory Check
$rlDir = sys_get_temp_dir() . '/fm_ratelimit';
$checks[] = [
    'name' => 'Rate Limit Storage',
    'status' => is_dir($rlDir) && is_writable($rlDir) ? 'PASS' : 'FAIL',
    'detail' => "Path: $rlDir"
];

// Output Report
echo "Security Audit Report\n";
echo "=====================\n";
$hasFailures = false;

foreach ($checks as $check) {
    if ($check['status'] === 'FAIL')
        $hasFailures = true;

    // Colorize
    $statusStr = $check['status']; // Add ANSI codes if desired

    printf("[%s] %s\n    %s\n", $statusStr, $check['name'], $check['detail']);
}

echo "\nResult: " . ($hasFailures ? "FAILURES DETECTED" : "ALL CHECKS PASSED") . "\n";
exit($hasFailures ? 1 : 0);

