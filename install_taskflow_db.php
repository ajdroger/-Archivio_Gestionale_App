<?php

require __DIR__ . '/vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Get PDO
try {
    $pdo = DatabaseConnection::getConnection();
    echo "Connected to database successfully.\n";
} catch (\Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Read SQL file
$sqlFile = __DIR__ . '/taskflow_schema.sql';
if (!file_exists($sqlFile)) {
    $sqlFile = 'C:/Users/aj_93/.gemini/antigravity/brain/674a08ad-ee3a-4b5d-9b4d-ad78696c5ed3/taskflow_schema.sql';
}

if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "TaskFlow Schema executed successfully. Tables created.\n";
} catch (\PDOException $e) {
    die("SQL execution failed: " . $e->getMessage() . "\n");
}
