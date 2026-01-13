<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    $db = DatabaseConnection::getConnection();

    echo "=== CREAZIONE UTENTI DI TEST ===\n";

    // 1. Assicuriamoci che l'admin abbia il ruolo corretto 'admin' (non 'Amministratore')
    $hashAdmin = password_hash('password', PASSWORD_DEFAULT);
    $db->prepare("INSERT OR REPLACE INTO users (id, username, password_hash, role, created_at) VALUES (1, 'admin', ?, 'admin', datetime('now'))")
        ->execute([$hashAdmin]);
    echo "[+] Utente 'admin' pronto (password: password)\n";

    // 2. Creazione utente Segreteria (Operatore)
    $hashSegreteria = password_hash('segreteria', PASSWORD_DEFAULT);
    $db->prepare("INSERT OR REPLACE INTO users (username, password_hash, role, created_at) VALUES ('segreteria_soci', ?, 'operator', datetime('now'))")
        ->execute([$hashSegreteria]);
    echo "[+] Utente 'segreteria_soci' pronto (password: segreteria, ruolo: operator)\n";

    echo "=== OPERAZIONE COMPLETATA ===\n";

} catch (Exception $e) {
    echo "[ERRORE] " . $e->getMessage() . "\n";
}

