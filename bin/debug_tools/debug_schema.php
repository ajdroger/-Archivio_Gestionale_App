<?php
require __DIR__ . '/../../vendor/autoload.php';
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
try {
    $p = DatabaseConnection::getConnection();
    echo "--- HASH COLUMN ---\n";
    $stmt = $p->query("SHOW COLUMNS FROM documenti LIKE '%hash%'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
