<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

echo "Adding user_id to audit_logs...\n";
try {
    $pdo->exec("ALTER TABLE audit_logs ADD COLUMN user_id INT DEFAULT NULL AFTER timestamp");
    echo "SUCCESS: user_id column added.\n";
} catch (PDOException $e) {
    echo "INFO: " . $e->getMessage() . " (may already exist)\n";
}
