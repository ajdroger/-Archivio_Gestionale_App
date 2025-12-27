<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

/**
 * Script Ripristino Completo (FACTORY RESET)
 * 
 * Esegue in sequenza TUTTI gli script di ripristino:
 * 1. Wipe Soci & Documenti (e restore 500)
 * 2. Wipe Users (e restore 14)
 * 3. Wipe Audit Logs
 * 
 * È l'opzione nucleare per tornare a uno stato pulito e popolato.
 */

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

echo "\n================================================\n";
echo "   FACTORY RESET (RIPRISTINO TOTALE)            \n";
echo "================================================\n";
echo "ATTENZIONE: Questo script cancellerà TUTTI i dati.\n";
echo "Avvio tra 3 secondi...\n";
sleep(3);

try {
    // 1. Restore Users
    echo "\n--- FASE 1: RIPRISTINO UTENTI ---\n";
    include __DIR__ . '/restore_users_14.php';

    // 2. Restore Soci
    echo "\n--- FASE 2: RIPRISTINO SOCI (500) ---\n";
    // Nota: restore_soci_500 usa autoload, quindi attenzione a ridefinizioni
    // Meglio eseguirlo come processo separato o logica inclusa
    // Per semplicità qui invochiamo lo script via shell se possibile, o includiamo con cura.
    // L'include puro potrebbe causare "cannot redeclare class" se non usiamo require_once e namespace corretti.
    // Ma gli script bin/ sono procedurali.
    // Eseguiamo via passthru per isolamento.
    passthru("php " . __DIR__ . "/restore_soci_500.php");

    // 3. Reset Audit
    echo "\n--- FASE 3: RESET AUDIT LOGS ---\n";
    include __DIR__ . '/reset_audit_logs.php';

    echo "\n\n================================================\n";
    echo "[SUCCESSO] FACTORY RESET COMPLETATO!\n";
    echo "Il sistema è stato riportato allo stato originale.\n";
    echo "================================================\n";

} catch (\Exception $e) {
    echo "\n[ERRORE CRITICO] " . $e->getMessage() . "\n";
    exit(1);
}
