<?php
/**
 * Script di migrazione One-Off per aggiungere le colonne di profilazione completa alla tabella 'soci'.
 * Eseguire da riga di comando: php bin/migrations/add_profiling_columns.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use Dotenv\Dotenv;

echo "Inizio migrazione DB: Aggiunta colonne Profilazione Socio...\n";

// Caricamento variabili d'ambiente (.env)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
try {
    $dotenv->load();
} catch (\Exception $e) {
    echo "Warning: .env file not found or invalid. Using defaults.\n";
}

try {
    $pdo = DatabaseConnection::getConnection();
} catch (\Exception $e) {
    die("Errore connessione DB: " . $e->getMessage() . "\n");
}

// Lista delle modifiche da applicare
$alterations = [
    "ADD COLUMN sesso CHAR(1) DEFAULT NULL AFTER data_nascita", // M/F
    "ADD COLUMN luogo_nascita VARCHAR(100) DEFAULT NULL AFTER sesso",
    "ADD COLUMN stato_civile VARCHAR(50) DEFAULT NULL AFTER luogo_nascita",

    // Dati Militari
    "ADD COLUMN grado VARCHAR(100) DEFAULT NULL AFTER telefono",
    "ADD COLUMN corpo_appartenenza VARCHAR(150) DEFAULT NULL AFTER grado",
    "ADD COLUMN data_arruolamento DATE DEFAULT NULL AFTER corpo_appartenenza",
    "ADD COLUMN data_congedo DATE DEFAULT NULL AFTER data_arruolamento",

    // Dati Civili Extra
    "ADD COLUMN titolo_studio VARCHAR(100) DEFAULT NULL AFTER data_congedo",
    "ADD COLUMN professione VARCHAR(100) DEFAULT NULL AFTER titolo_studio",

    // Dati Sanitari / Emergenza
    "ADD COLUMN gruppo_sanguigno VARCHAR(10) DEFAULT NULL AFTER professione",
    "ADD COLUMN note_mediche TEXT DEFAULT NULL AFTER gruppo_sanguigno",
    "ADD COLUMN contatto_emergenza VARCHAR(255) DEFAULT NULL AFTER note_mediche"
];

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($alterations as $sql) {
    try {
        $fullQuery = "ALTER TABLE soci $sql";
        $pdo->exec($fullQuery);
        echo "[OK] $sql\n";
        $successCount++;
    } catch (PDOException $e) {
        // Codice errore 42S21 = Column already exists; 1060 = Duplicate column name
        if ($e->getCode() == '42S21' || $e->getCode() == 1060 || str_contains($e->getMessage(), 'Duplicate column')) {
            echo "[SKIP] Colonna già esistente: $sql\n";
            $skipCount++;
        } else {
            echo "[ERROR] Fallito: $sql. Motivo: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
}

echo "\n--- Migrazione terminata ---\n";
echo "Eseguiti: $successCount\n";
echo "Saltati: $skipCount\n";
echo "Errori: $errorCount\n";

if ($errorCount > 0) {
    exit(1);
}
exit(0);

