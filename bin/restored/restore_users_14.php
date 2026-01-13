<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

/**
 * Script di Ripristino Utenti (14)
 * 
 * Questo strumento "One-Click" ripristina 14 utenze di sistema predefinite.
 * Include 2 Admin, 5 Operatori e 7 Visualizzatori/Segreteria.
 * Password di default: 'password123' (hashata).
 */

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

echo "\n================================================\n";
echo "   RIPRISTINO UTENTI (14 ACCOUNT) - ONE CLICK    \n";
echo "================================================\n";

try {
    $pdo = DatabaseConnection::getConnection();

    // 1. Cleanup
    echo "[!] Svuotamento tabella 'users'...\n";
    $pdo->exec("TRUNCATE TABLE users");
    echo "[OK] Tabella utenti svuotata.\n";

    // 2. Prepare Users List
    // Default password for all: password123
    $defaultPass = password_hash('password123', PASSWORD_BCRYPT);

    $users = [
        // ADMINS (2)
        ['username' => 'admin', 'role' => 'admin', 'desc' => 'Amministratore Capo'],
        ['username' => 'sysadmin', 'role' => 'admin', 'desc' => 'System Admin'],

        // OPERATORI (5)
        ['username' => 'operatore1', 'role' => 'editor', 'desc' => 'Operatore Turno A'],
        ['username' => 'operatore2', 'role' => 'editor', 'desc' => 'Operatore Turno B'],
        ['username' => 'mario.rossi', 'role' => 'editor', 'desc' => 'Ufficio Iscrizioni'],
        ['username' => 'luigi.verdi', 'role' => 'editor', 'desc' => 'Ufficio Rinnovi'],
        ['username' => 'giulia.bianchi', 'role' => 'editor', 'desc' => 'Gestione Documentale'],

        // VIEWER/SEGRETERIA (7)
        ['username' => 'segreteria', 'role' => 'viewer', 'desc' => 'Segreteria Generale'],
        ['username' => 'visualizzatore1', 'role' => 'viewer', 'desc' => 'Audit Esterno'],
        ['username' => 'visualizzatore2', 'role' => 'viewer', 'desc' => 'Consultazione Storica'],
        ['username' => 'anna.neri', 'role' => 'viewer', 'desc' => 'Front Desk'],
        ['username' => 'paolo.gialli', 'role' => 'viewer', 'desc' => 'Supporto Tecnico'],
        ['username' => 'archivista', 'role' => 'viewer', 'desc' => 'Archivio Cartaceo'],
        ['username' => 'guest', 'role' => 'viewer', 'desc' => 'Ospite Monitoraggio']
    ];

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, created_at) VALUES (:username, :pass, :role, NOW())");

    echo "[+] Inserimento account...\n";

    foreach ($users as $u) {
        $stmt->execute([
            ':username' => $u['username'],
            ':pass' => $defaultPass,
            ':role' => $u['role']
        ]);
        echo "   - Creata utenza: {$u['username']} ({$u['role']}) - {$u['desc']}\n";
    }

    echo "\n\n================================================\n";
    echo "[SUCCESSO] Ripristinati 14 Utenti di Sistema!\n";
    echo "  - Password di default per tutti: 'password123'\n";
    echo "  - Ruoli assegnati correttamente (Admin, Editor, Viewer)\n";
    echo "================================================\n";

} catch (\Exception $e) {
    echo "\n[ERRORE CRITICO] " . $e->getMessage() . "\n";
    exit(1);
}

