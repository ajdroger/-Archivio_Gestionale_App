<?php
require_once __DIR__ . '/vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = DatabaseConnection::getConnection();

    $ip = '127.0.0.1'; // Test IP
    $ua = 'Debug Script';
    $failCount = 8;
    $username = 'test_attacker';

    echo "Attempting INSERT...\n";

    $stmtThreat = $db->prepare("INSERT INTO traffic_logs (ip_address, user_agent, request_uri, method, threat_score, risk_level, geodata, created_at) VALUES (:ip, :ua, '/auth/login', 'POST', 85, 'CRITICAL', :geodata, NOW())");

    $result = $stmtThreat->execute([
        ':ip' => $ip,
        ':ua' => $ua,
        ':geodata' => json_encode([
            'threat_type' => 'brute_force',
            'username_attempt' => $username,
            'status' => 'LOGIN_FAILED',
            'consecutive_failures' => $failCount,
            'lat' => 45.4642,
            'lon' => 9.1900
        ])
    ]);

    echo "Insert Result: " . ($result ? "SUCCESS" : "FAILURE") . "\n";
    if (!$result) {
        print_r($stmtThreat->errorInfo());
    }

    $id = $db->lastInsertId();
    echo "Inserted ID: $id\n";

} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
