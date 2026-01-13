<?php
require __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use MCAG\GraphQL\Schema;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use GraphQL\Utils\SchemaPrinter;

try {
    echo "--- GraphQL Schema Debugger ---\n";

    // 1. Database Connection
    $pdo = DatabaseConnection::getInstance()->getConnection();
    echo "[OK] DB Connection\n";

    // 2. Repository
    $repo = new PDOSocioRepository($pdo);
    echo "[OK] Repository Repo\n";

    // 3. Schema Build
    $schema = Schema::build($repo);
    echo "[OK] Schema Built\n";

    // 4. Print Schema
    echo "\n--- Schema Definition ---\n";
    echo SchemaPrinter::doPrint($schema);
    echo "\n-------------------------\n";

} catch (\Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

