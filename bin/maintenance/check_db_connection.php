<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "=== DATABASE CONNECTION TEST ===\n";

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );

    echo "✓ MySQL Connection: OK\n";
    echo "✓ Database: " . $_ENV['DB_DATABASE'] . "\n\n";

    $tables = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    echo "Tables count: $tables\n";

    $soci = $pdo->query("SELECT COUNT(*) FROM soci")->fetchColumn();
    echo "Soci count: $soci\n";

    $docs = $pdo->query("SELECT COUNT(*) FROM documenti")->fetchColumn();
    echo "Documenti count: $docs\n";

    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users count: $users\n";

    echo "\n=== ALL CHECKS PASSED ===\n";
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

