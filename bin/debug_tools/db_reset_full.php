<?php
require __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Dropping table $table...\n";
    $pdo->exec("DROP TABLE IF EXISTS `$table`");
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "All tables dropped.\n";
