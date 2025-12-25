<?php

require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\Debug\SystemCheck;
use FratellanzaMilitare\Debug\QueryLogger;

echo "Fratellanza Militare - Debug Console\n";
echo "1. Run System Diagnostics\n";
echo "2. Inspect Database Tables\n";
echo "3. List Recent Logs\n";
echo "4. Clear Query Log\n";
echo "5. Repair Permissions\n";

$choice = 1;

if ($argc > 1) {
    $choice = (int) $argv[1];
}

switch ($choice) {
    case 1:
        $check = new SystemCheck();
        $check->printReport();
        break;
    case 2:
        try {
            $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
            $inspector = new \FratellanzaMilitare\Debug\DatabaseInspector($db);
            echo "Stato: " . $inspector->checkIntegrity() . "\n";
            foreach ($inspector->getTablesSummary() as $table) {
                echo "- {$table['name']}: {$table['rows']} record\n";
            }
        } catch (Exception $e) {
            echo "[FAIL] Database Error: " . $e->getMessage() . "\n";
        }
        break;
    case 3:
        $logViewer = new \FratellanzaMilitare\Debug\LogViewer();
        foreach ($logViewer->listLogs() as $log) {
            echo "- {$log['name']} ({$log['size']})\n";
        }
        break;
    case 4:
        $logger = new QueryLogger();
        $logger->clearLog();
        echo "[OK] Query log cleared.\n";
        break;
    case 5:
        require __DIR__ . '/repair_tool.php';
        break;
    default:
        echo "Invalid operation.\n";
}
