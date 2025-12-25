<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Debug/SystemCheck.php';
require_once __DIR__ . '/../src/Debug/DatabaseInspector.php';
require_once __DIR__ . '/../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use FratellanzaMilitare\Debug\SystemCheck;
use FratellanzaMilitare\Debug\DatabaseInspector;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

echo "--- Diagnostica di Sistema ---\n";
$checker = new SystemCheck();
$checker->printReport();

echo "\n--- Analisi Database ---\n";
try {
    $db = DatabaseConnection::getConnection();
    $inspector = new DatabaseInspector($db);
    echo "Integrità: " . $inspector->checkIntegrity() . "\n";
    echo "Dimensione: " . $inspector->getDatabaseSize() . "\n";
    echo "Tabelle:\n";
    foreach ($inspector->getTablesSummary() as $table) {
        echo "- {$table['name']}: {$table['rows']} record\n";
    }
} catch (Exception $e) {
    echo "Errore Database: " . $e->getMessage() . "\n";
}
