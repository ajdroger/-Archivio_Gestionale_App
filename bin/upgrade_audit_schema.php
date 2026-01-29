<?php
require __DIR__ . '/../vendor/autoload.php';

// Load Config
if (file_exists(__DIR__ . '/../config/settings.php')) {
    $settings = require __DIR__ . '/../config/settings.php';
} else {
    // Fallback or attempt to bootstrap app logic to get container
    // For simplicity, we assume standard setup or try to instantiate DB manually if needed
    // But let's verify if we can use the App's container.
    echo "Config not found, relying on manual PDO if needed.\n";
}

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    // 1. Get PDO Connection
    // Assuming DatabaseConnection class works or we pull from standard config
    // Let's try to include the actual bootstrap if possible, but manual is safer for a script

    // Quick Hack: Parse config directly
    $dbConfig = $settings['settings']['db'] ?? [];
    if (empty($dbConfig)) {
        // Try to read generic config
        $dbConfig = [
            'host' => '127.0.0.1',
            'database' => 'mcag_db',
            'username' => 'root',
            'password' => 'mysql'
        ];
    }

    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to Database.\n";

    // 2. Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM audit_logs LIKE 'resolved_at'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Adding 'resolved_at' column...\n";
        $pdo->exec("ALTER TABLE audit_logs ADD COLUMN resolved_at DATETIME NULL DEFAULT NULL AFTER user_agent");
        echo "Column 'resolved_at' added successfully.\n";
    } else {
        echo "Column 'resolved_at' already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
