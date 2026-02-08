<?php
require_once __DIR__ . '/vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = DatabaseConnection::getConnection();

echo "\n--- TRAFFIC_LOGS COLUMNS ---\n";
$stmt = $db->query("DESCRIBE traffic_logs");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($cols as $col) {
    echo "- $col\n";
}
