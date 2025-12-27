<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

/**
 * Script di Ripristino Audit Log
 * 
 * Questo strumento "One-Click" svuota la tabella audit_logs 
 * e reinizializza il registro con un evento di sistema "Log Reset".
 * Utile per pulire lo storico in ambiente di sviluppo.
 */

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

echo "\n================================================\n";
echo "   RIPRISTINO AUDIT LOGS - ONE CLICK           \n";
echo "================================================\n";

try {
    $pdo = DatabaseConnection::getConnection();

    echo "[!] Eliminazione di tutti i log di audit...\n";
    $pdo->exec("TRUNCATE TABLE audit_logs");
    echo "[OK] Tabella audit_logs svuotata.\n";

    // Insert System Reset Event
    echo "[+] Inserimento evento di sistema 'LOG_RESET'...\n";
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, resource_id, ip_address, timestamp) VALUES (NULL, 'SYSTEM_LOG_RESET', 'audit_logs', '127.0.0.1', NOW())");
    $stmt->execute();

    echo "\n\n================================================\n";
    echo "[SUCCESSO] Audit Log ripristinato a zero.\n";
    echo "================================================\n";

} catch (\Exception $e) {
    echo "\n[ERRORE CRITICO] " . $e->getMessage() . "\n";
    exit(1);
}
