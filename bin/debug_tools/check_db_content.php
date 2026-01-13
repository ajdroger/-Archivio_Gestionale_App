<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    $db = DatabaseConnection::getConnection();
    $stmt = $db->query("SELECT * FROM soci");
    $soci = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== CONTENUTO TABELLA SOCI ===\n";
    if (empty($soci)) {
        echo "Nessun socio trovato.\n";
    } else {
        foreach ($soci as $s) {
            echo "[OK] CF: {$s['codice_fiscale']} - Nome: {$s['nome']} {$s['cognome']} - Stato: {$s['stato']}\n";
        }
    }
} catch (Exception $e) {
    echo "[ERRORE] " . $e->getMessage() . "\n";
}

