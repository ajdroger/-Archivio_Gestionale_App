<?php
require_once __DIR__ . '/../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    $db = DatabaseConnection::getConnection();
    echo "Controllo esistenza colonna 'telefono'...\n";

    // Check if column exists
    $cols = $db->query("PRAGMA table_info(soci)")->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'telefono') {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        echo "Aggiunta colonna 'telefono' alla tabella 'soci'...\n";
        $db->exec("ALTER TABLE soci ADD COLUMN telefono TEXT DEFAULT ''");
        echo "Colonna aggiunta con successo.\n";
    } else {
        echo "La colonna 'telefono' esiste già.\n";
    }

} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
    exit(1);
}
