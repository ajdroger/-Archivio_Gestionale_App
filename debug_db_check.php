<?php
require_once __DIR__ . '/vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = DatabaseConnection::getConnection();

echo "--- LAST 5 AUDIT LOGS ---\n";
$stmt = $db->query("SELECT id, action, ip_address, timestamp FROM audit_logs ORDER BY id DESC LIMIT 5");
$audits = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($audits as $row) {
    echo "[{$row['id']}] {$row['timestamp']} | {$row['action']} | {$row['ip_address']}\n";
}

echo "\n--- LAST 5 TRAFFIC LOGS ---\n";
$stmt2 = $db->query("SELECT id, threat_type, ip_address, created_at FROM traffic_logs ORDER BY id DESC LIMIT 5");
$traffic = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($traffic as $row) {
    echo "[{$row['id']}] {$row['created_at']} | {$row['threat_type']} | {$row['ip_address']}\n";
}

echo "\n--- COUNT CHECK ---\n";
$ip = '::1'; // Assuming localhost, will check both
$stmtCount = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE ip_address = :ip AND action = 'LOGIN_FAILED' AND timestamp >= NOW() - INTERVAL 15 MINUTE");
$stmtCount->execute([':ip' => $ip]);
echo "Count for ::1 : " . $stmtCount->fetchColumn() . "\n";

$ip2 = '127.0.0.1';
$stmtCount->execute([':ip' => $ip2]);
echo "Count for 127.0.0.1 : " . $stmtCount->fetchColumn() . "\n";
