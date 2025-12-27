<?php
require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $pdo = DatabaseConnection::getConnection();

    echo "--- USERS TABLE ---\n";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "ID: {$u['id']} | User: {$u['username']} | Role: {$u['role']}\n";
    }

    echo "\n--- TOTP SECRET COLUMN TYPE ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'totp_secret'");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
