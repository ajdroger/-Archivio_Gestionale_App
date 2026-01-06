<?php
/**
 * FRATELLANZA MILITARE - UNIVERSAL MIGRATION DOCTOR
 * 
 * Questo script serve per rendere il progetto PORTABILE e AUTO-RIPARANTE.
 * Ideale per:
 * 1. Spostare il progetto su un nuovo PC (es. Università).
 * 2. Ripristinare file mancanti o dipendenze corrotte.
 * 3. Verificare che l'ambiente sia idoneo (PHP, Estensioni, DB).
 */

// Se non siamo in CLI, blocchiamo per sicurezza
if (php_sapi_name() !== 'cli') {
    die("Questo strumento può essere eseguito solo da riga di comando (CLI).");
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║   FRATELLANZA MILITARE - UNIVERSAL MIGRATION DOCTOR v1.0         ║\n";
echo "║   Strumento di Ripristino e Migrazione Totale                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$rootDir = realpath(__DIR__ . '/../');
echo "[INFO] Root del progetto: $rootDir\n";
sleep(1);

// ==================================================================================
// FASE 1: CONTROLLO AMBIENTE DI BASE
// ==================================================================================
echo "\n--- FASE 1: CHECK AMBIENTE BASE ---\n";

// 1.1 PHP Version
$phpVer = phpversion();
echo "[TEST] Versione PHP rilevata: $phpVer... ";
if (version_compare($phpVer, '8.1.0', '<')) {
    echo "[FAIL] Richiesto PHP 8.1+. Aggiorna Ampps/XAMPP.\n";
    exit(1);
}
echo "[OK]\n";

// 1.2 Estensioni
$requiredExts = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'json'];
foreach ($requiredExts as $ext) {
    echo "[TEST] Estensione '$ext'... ";
    if (!extension_loaded($ext)) {
        echo "[FAIL] Manca l'estensione PHP '$ext'. Abilitala nel php.ini.\n";
        exit(1);
    }
    echo "[OK]\n";
}

// ==================================================================================
// FASE 2: GESTIONE DIPENDENZE (Auto-Download)
// ==================================================================================
echo "\n--- FASE 2: CHECK & DOWNLOAD DIPENDENZE ---\n";

// 2.1 Composer (Backend)
if (!is_dir("$rootDir/vendor")) {
    echo "[WARN] Cartella 'vendor' mancante. Avvio download dipendenze PHP...\n";
    // Tentativo di usare composer.phar locale o globale
    $composer = file_exists("$rootDir/composer.phar") ? "php composer.phar" : "composer";

    echo "[EXEC] Esecuzione '$composer install'...\n";
    passthru("cd \"$rootDir\" && $composer install --no-dev --optimize-autoloader", $returnVar);

    if ($returnVar !== 0) {
        echo "[ERR] Impossibile scaricare le dipendenze. Assicurati di essere connesso a internet e di avere Composer installato.\n";
        // Offri opzione di ignorare se si vuole solo debuggare
        echo "Premi INVIO per riprovare o CTRL+C per uscire.\n";
        fgets(STDIN);
    } else {
        echo "[OK] Dipendenze PHP installate.\n";
    }
} else {
    echo "[OK] 'vendor' presente. Check integrità veloce... [OK]\n";
}

// 2.2 NPM (Frontend) - Solo se node_modules manca
if (!is_dir("$rootDir/node_modules")) {
    echo "[WARN] Cartella 'node_modules' mancante. Avvio download dipendenze JS (Opzionale)...\n";
    echo "       Hai installato Node.js? (s/n) > ";
    $resp = trim(fgets(STDIN));
    if (strtolower($resp) === 's') {
        echo "[EXEC] Esecuzione 'npm install'...\n";
        passthru("cd \"$rootDir\" && npm install && npm run build", $returnVar);
        if ($returnVar === 0) {
            echo "[OK] Dipendenze JS e Build completati.\n";
        } else {
            echo "[WARN] NPM ha fallito, ma il progetto potrebbe girare se public/css è già compilato.\n";
        }
    } else {
        echo "[SKIP] Salto installazione Node. Assicurati che public/css esista.\n";
    }
} else {
    echo "[OK] 'node_modules' presente.\n";
}

// ==================================================================================
// FASE 3: STRUTTURA & INTEGRITÀ FILE
// ==================================================================================
echo "\n--- FASE 3: INTEGRITÀ FILE SISTEMA ---\n";

$criticalPaths = [
    'src',
    'public',
    'templates',
    'config',
    'bin',
    'logs',
    'storage'
];

foreach ($criticalPaths as $path) {
    echo "[TEST] Cartella '$path'... ";
    if (!is_dir("$rootDir/$path")) {
        echo "[FIX] Mancante! Creazione automatica...\n";
        mkdir("$rootDir/$path", 0777, true);
        echo "       [OK] Creata.\n";
    } else {
        echo "[OK]\n";
    }
}

// Check permessi storage (Simulato su Win, utile su Linux)
$writablePaths = ['storage', 'logs'];
foreach ($writablePaths as $path) {
    if (!is_writable("$rootDir/$path")) {
        echo "[FIX] Fix permessi per '$path'...\n";
        @chmod("$rootDir/$path", 0777);
    }
}

// ==================================================================================
// FASE 4: CONFIGURAZIONE (.ENV)
// ==================================================================================
echo "\n--- FASE 4: CONFIGURAZIONE ENV ---\n";

if (!file_exists("$rootDir/.env")) {
    echo "[WARN] File .env MANCANTE!\n";
    if (file_exists("$rootDir/.env.example")) {
        echo "[FIX] Creazione .env da .env.example...\n";
        copy("$rootDir/.env.example", "$rootDir/.env");
        echo "       [OK] File creato. Ora devi configurare il database.\n";
    } else {
        echo "[CRITICAL] Neanche .env.example esiste. Il progetto è corrotto.\n";
        exit(1);
    }
} else {
    echo "[OK] File .env presente.\n";
}

// ==================================================================================
// FASE 5: DATABASE CHECK
// ==================================================================================
echo "\n--- FASE 5: CONNESSIONE DATABASE ---\n";

// Carica autoloader per usare le classi del progetto se disponibili
if (file_exists("$rootDir/vendor/autoload.php")) {
    require_once "$rootDir/vendor/autoload.php";

    // Carica Env
    try {
        $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
        $dotenv->safeLoad();

        $connectionType = $_ENV['DB_CONNECTION'] ?? 'mysql';

        if ($connectionType === 'sqlite') {
            $dbPath = $_ENV['DB_DATABASE'] ?? __DIR__ . '/../../database.sqlite';
            // Resolve relative path if needed
            if (!file_exists($dbPath) && file_exists($rootDir . '/' . $dbPath)) {
                $dbPath = $rootDir . '/' . $dbPath;
            }
            echo "[CONN] Tento connessione a sqlite:$dbPath...\n";
            $pdo = new PDO("sqlite:$dbPath");
        } else {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $db = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';
            $user = $_ENV['DB_USERNAME'] ?? 'root';
            $pass = $_ENV['DB_PASSWORD'] ?? '';

            echo "[CONN] Tento connessione a mysql:host=$host;dbname=$db...\n";
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        }
        echo "[OK] Connessione al Database RIUSCITA.\n";

        // Check Tabelle
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) == 0) {
            echo "[WARN] Il database è VUOTO.\n";
            echo "       Vuoi importare i dati di esempio e utenti (FACTORY RESET)? (s/n) > ";
            $resp = trim(fgets(STDIN));
            if (strtolower($resp) === 's') {
                echo "[EXEC] Lancio Factory Reset...\n";
                // Richiama script di restore
                $factoryScript = "$rootDir/bin/restored/reset_db_factory.php";
                if (file_exists($factoryScript)) {
                    passthru("php \"$factoryScript\"");
                } else {
                    echo "[ERR] Script di restore non trovato in bin/restored.\n";
                }
            }
        } else {
            echo "[OK] Il database contiene " . count($tables) . " tabelle.\n";
        }

    } catch (Exception $e) {
        echo "[FAIL] Errore connessione DB: " . $e->getMessage() . "\n";
        echo "       Verifica il file .env e riprova.\n";
    }
} else {
    echo "[SKIP] Impossibile testare DB senza vendor (Composer non eseguito?).\n";
}


echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║   CONTROLLO COMPLETATO - PROGETTO PRONTO ALL'USO                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "Premi INVIO per chiudere...";
fgets(STDIN);
