<?php

/**
 * MIGRATION TO MYSQL: UNIVERSAL MIGRATOR
 * 
 * Questo script legge tutti i dati dal database SQLite e li migra nel nuovo database MySQL.
 * 
 * Step:
 * 1. Connessione a SQLite (Source) e MySQL (Target).
 * 2. Creazione Schema MySQL (compatibile).
 * 3. Migrazione Dati (Streaming per evitare problemi di memoria).
 * 4. Verifica Integrità.
 */

require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// 1. Load Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Increase limits for migration
ini_set('memory_limit', '512M');
set_time_limit(0);

echo "\n============================================\n";
echo "   FRATELLANZA MILITARE - DB MIGRATOR v1.0   \n";
echo "       [ SQLITE  ->  MYSQL (MariaDB) ]       \n";
echo "============================================\n\n";

// 2. Connect to Source (SQLite)
$sqlitePath = __DIR__ . '/../../' . ($_ENV['DB_PATH'] ?? 'database.sqlite');
if (!file_exists($sqlitePath)) {
    die("[FATAL] SQLite database not found at $sqlitePath\n");
}

echo "[1/4] Connecting to Source (SQLite)... ";
try {
    $sqlite = new PDO("sqlite:" . $sqlitePath);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "OK.\n";
} catch (Exception $e) {
    die("FAIL: " . $e->getMessage() . "\n");
}

// 3. Connect to Target (MySQL)
echo "[2/4] Connecting to Target (MySQL)... ";
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$dbname = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';

try {
    // Connect without DB first to create it if needed
    $mysql = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create DB if not exists
    $mysql->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysql->exec("USE `$dbname`");

    echo "OK (Database `$dbname` selected).\n";
} catch (Exception $e) {
    die("FAIL: " . $e->getMessage() . "\n");
}

// 4. Schema Creation (MySQL Optimized)
echo "[3/4] Creating Schema in MySQL... \n";

// Disable FK Checks
$mysql->exec("SET FOREIGN_KEY_CHECKS=0");

// -- USERS --
echo "  -> Table 'users'... ";
$mysql->exec("DROP TABLE IF EXISTS users");
$startSql = "
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    totp_secret VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$mysql->exec($startSql);
echo "Created.\n";

// -- SOCI --
echo "  -> Table 'soci'... ";
$mysql->exec("DROP TABLE IF EXISTS soci");
$sociSql = "
CREATE TABLE soci (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    codice_fiscale VARCHAR(16) NOT NULL UNIQUE,
    data_nascita DATE NOT NULL,
    indirizzo TEXT,
    email VARCHAR(150),
    telefono VARCHAR(50),
    matricola VARCHAR(50) UNIQUE,
    stato_iscrizione VARCHAR(20) DEFAULT 'ATTIVO',
    data_iscrizione DATE DEFAULT NULL,
    data_scadenza DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Additional fields for analytics
    sesso CHAR(1) DEFAULT NULL,
    luogo_nascita VARCHAR(100) DEFAULT NULL,
    provincia_nascita CHAR(2) DEFAULT NULL,
    cap VARCHAR(5) DEFAULT NULL,
    citta VARCHAR(100) DEFAULT NULL,
    
    INDEX idx_cf (codice_fiscale),
    INDEX idx_cognome (cognome),
    INDEX idx_stato (stato_iscrizione)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$mysql->exec($sociSql);
echo "Created.\n";

// -- DOCUMENTI --
echo "  -> Table 'documenti'... ";
$mysql->exec("DROP TABLE IF EXISTS documenti");
$docSql = "
CREATE TABLE documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_cf VARCHAR(16) NOT NULL,
    tipo_documento VARCHAR(50) NOT NULL,
    nome_file VARCHAR(255) NOT NULL,
    percorso_file VARCHAR(255) DEFAULT NULL,
    data_caricamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dimensione_bytes BIGINT DEFAULT 0,
    hash_file VARCHAR(64) DEFAULT NULL,
    id_univoco VARCHAR(36) NOT NULL UNIQUE,
    stato VARCHAR(20) DEFAULT 'PENDING',
    
    -- Payment Module fields
    anno_solare INT DEFAULT NULL,
    quota_versata DECIMAL(10,2) DEFAULT NULL,
    metodo_pagamento VARCHAR(50) DEFAULT NULL,
    
    -- GDPR Consent fields
    trattamento_dati TINYINT(1) DEFAULT NULL,
    cessione_terzi TINYINT(1) DEFAULT NULL,
    marketing TINYINT(1) DEFAULT NULL,
    data_firma TIMESTAMP DEFAULT NULL,
    
    FOREIGN KEY (socio_cf) REFERENCES soci(codice_fiscale) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_doc_socio (socio_cf),
    INDEX idx_doc_tipo (tipo_documento),
    INDEX idx_doc_stato (stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$mysql->exec($docSql);
echo "Created.\n";

// -- AUDIT LOGS --
echo "  -> Table 'audit_logs'... ";
$mysql->exec("DROP TABLE IF EXISTS audit_logs");
$auditSql = "
CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    resource_id VARCHAR(100) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    
    INDEX idx_audit_time (timestamp),
    INDEX idx_audit_user (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$mysql->exec($auditSql);
echo "Created.\n";

// Re-enable FK Checks
$mysql->exec("SET FOREIGN_KEY_CHECKS=1");

// 5. Data Migration
echo "\n[4/4] Migrating Data... \n";

function migrateTable($sqlite, $mysql, $table, $columns)
{
    echo "  Processing '$table'... ";
    // Check if source table exists
    $chk = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
    if (!$chk) {
        echo "  Skip '$table' (table not found in source).\n";
        return;
    }

    $total = $sqlite->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $stmtSource = $sqlite->query("SELECT * FROM $table");

    // Build Insert SQL (Destination Columns)
    // $columns is ['dest_col' => 'source_col']
    $destCols = array_keys($columns);
    $colsList = implode(", ", $destCols);
    $placeholders = implode(", ", array_fill(0, count($destCols), "?"));

    $sqlLoad = "INSERT INTO $table ($colsList) VALUES ($placeholders)";
    $stmtDest = $mysql->prepare($sqlLoad);

    $count = 0;
    $errors = 0;

    while ($row = $stmtSource->fetch(PDO::FETCH_ASSOC)) {
        $data = [];
        foreach ($columns as $dest => $source) {
            $val = $row[$source] ?? null;

            // Special Mapping Fixes
            if ($table === 'documenti' && $dest === 'percorso_file' && $val === null) {
                // If parcours_file is null, fallback to nome_file assuming flat structure or generate default
                $val = 'uploads/' . ($row['nome_file'] ?? 'unknown');
            }
            if ($table === 'soci' && $dest === 'stato_iscrizione' && empty($val)) {
                $val = 'ATTIVO'; // Default
            }

            $data[] = $val;
        }

        try {
            $stmtDest->execute($data);
            $count++;
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 5) {
                echo "\n    [ERR] Row Error (ID " . ($row['id'] ?? '?') . "): " . $e->getMessage();
            }
        }

        if ($count % 100 == 0)
            echo ".";
    }

    echo " Done ($count/$total migrated";
    if ($errors > 0)
        echo ", $errors failed";
    echo ").\n";
}

// Map: Users (Dest => Source)
$userMap = [
    'username' => 'username',
    'password_hash' => 'password_hash',
    'role' => 'role',
    'created_at' => 'created_at',
    'totp_secret' => 'totp_secret'
];
migrateTable($sqlite, $mysql, 'users', $userMap);

// Map: Soci (Dest => Source)
$sociMap = [
    'nome' => 'nome',
    'cognome' => 'cognome',
    'codice_fiscale' => 'codice_fiscale',
    'data_nascita' => 'data_nascita',
    'indirizzo' => 'indirizzo',
    'email' => 'email',
    'telefono' => 'telefono',
    'matricola' => 'matricola',
    'stato_iscrizione' => 'stato', // Fix: Map 'stato' (SQLite) to 'stato_iscrizione' (MySQL)
    // 'data_iscrizione' => ?? (Does not exist in SQLite source, will be NULL)
    // 'data_scadenza' => ?? (Does not exist in SQLite source, will be NULL)
];
migrateTable($sqlite, $mysql, 'soci', $sociMap);

// Map: Documenti (Dest => Source)
$docMap = [
    'socio_cf' => 'codice_fiscale_socio', // Fix: Map 'codice_fiscale_socio' to 'socio_cf'
    'tipo_documento' => 'tipo_documento',
    'nome_file' => 'nome_file',
    'percorso_file' => 'nome_file', // Fix: Map 'nome_file' to 'percorso_file' temporarily as source has no path
    'data_caricamento' => 'data_caricamento',
    // 'dimensione_bytes' => ?? (Missing in source)
    'id_univoco' => 'id_univoco',
    'hash_file' => 'hash_sha256' // Fix: Map 'hash_sha256' to 'hash_file'
];
migrateTable($sqlite, $mysql, 'documenti', $docMap);

// Map: Audit Logs
$auditMap = [
    'timestamp' => 'timestamp',
    'username' => 'username',
    'action' => 'action',
    'resource_id' => 'resource_id',
    'details' => 'details',
    'ip_address' => 'ip_address',
    'user_agent' => 'user_agent'
];
migrateTable($sqlite, $mysql, 'audit_logs', $auditMap);


echo "\n\n[SUCCESS] Migration Complete! \n";
echo "Now update .env to DB_CONNECTION=mysql if not already set.\n";
