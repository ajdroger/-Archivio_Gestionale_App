<?php

require __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

/**
 * Script di Audit Integrità Database.
 * Verifica la presenza di vincoli chiavi esterne e record orfani.
 */

try {
    // Carica variabili d'ambiente
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    echo "--- Database Integrity Audit ---\n";

    $pdo = DatabaseConnection::getConnection();
    $dbName = $_ENV['DB_DATABASE'];

    // 1. Verifica Vincoli Chiavi Esterne (Foreign Keys)
    echo "\n[1] Checking Foreign Key Constraints...\n";
    $stmt = $pdo->prepare("
        SELECT TABLE_NAME, CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");
    $stmt->execute([$dbName]);
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($fks)) {
        echo "\033[33m[WARNING] No Foreign Keys found! Database integrity relies on application logic.\033[0m\n";
    } else {
        foreach ($fks as $fk) {
            echo "[OK] Found FK: {$fk['CONSTRAINT_NAME']} on table {$fk['TABLE_NAME']}\n";
        }
    }

    // 2. Verifica Record Orfani (Esempio: Documenti senza Socio)
    echo "\n[2] Checking for Orphaned Records (Example: Documenti)...\n";
    // Check if tables exist first
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('documenti', $tables) && in_array('soci', $tables)) {
        $orphans = $pdo->query("
            SELECT COUNT(*) 
            FROM documenti 
            WHERE socio_cf NOT IN (SELECT codice_fiscale FROM soci)
        ")->fetchColumn();

        if ($orphans > 0) {
            echo "\033[31m[FAIL] Found $orphans orphaned records in 'documenti' table!\033[0m\n";
        } else {
            echo "[OK] No orphaned records in 'documenti'.\n";
        }
    } else {
        echo "[SKIP] Tables 'documenti' or 'soci' not found.\n";
    }

    echo "\nAudit Complete.\n";

} catch (Exception $e) {
    echo "\033[31m[ERROR] " . $e->getMessage() . "\033[0m\n";
    exit(1);
}

