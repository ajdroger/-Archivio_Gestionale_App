<?php

require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\SecurityLayer\AuditTrail;

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    echo "Simulating Live Audit Event...\n";
    $pdo = DatabaseConnection::getConnection();

    $audit = AuditTrail::getInstance();
    $audit->setPdo($pdo);

    // Log a manual event
    $audit->logEvento(null, 'SYSTEM_CHECK_LIVE', 'DevTools Verification');

    echo "Done. Check DevTools now.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
