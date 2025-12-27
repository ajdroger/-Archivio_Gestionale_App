<?php

require __DIR__ . '/../../vendor/autoload.php';

echo "Checking .env file...\n";
if (file_exists(__DIR__ . '/../../.env')) {
    echo ".env found.\n";
} else {
    echo ".env NOT found.\n";
    exit(1);
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
    echo "Dotenv loaded successfully.\n";
} catch (Exception $e) {
    echo "Dotenv Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "DB_HOST: " . $_ENV['DB_HOST'] . "\n";
echo "DB_DATABASE: " . $_ENV['DB_DATABASE'] . "\n";

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    echo "PDO Connection Successful.\n";
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
    exit(1);
}
