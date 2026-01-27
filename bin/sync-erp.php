<?php
/**
 * MCAG ERP Synchronizer
 * Usage: php bin/sync-erp.php [--dry-run]
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MCAG\Integration\ERP\ZucchettiAdapter;

// Load Env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

echo "--------------------------------------------------\n";
echo "   MCAG Enterprise ERP Sync v1.0                  \n";
echo "--------------------------------------------------\n";

// Configuration (Mocked via Env or Defaults)
$erpType = $_ENV['ERP_PROVIDER'] ?? 'ZUCCHETTI';
$erpHost = $_ENV['ERP_HOST'] ?? 'https://api.zucchetti.com/hr';
$erpKey = $_ENV['ERP_KEY'] ?? 'mock-key-123';

$adapter = null;

try {
    switch (strtoupper($erpType)) {
        case 'ZUCCHETTI':
            echo "[*] Initializing Zucchetti HR Adapter...\n";
            $adapter = new ZucchettiAdapter($erpHost, $erpKey);
            break;
        case 'SAP':
            echo "[*] SAP Business One Adapter not yet enabled.\n";
            exit(0);
        default:
            die("[!] Unknown ERP Provider: $erpType\n");
    }

    if ($adapter->connect()) {
        echo "[+] Connected to " . $adapter->getProviderName() . "\n";

        $since = date('Y-m-d', strtotime('-1 day'));
        echo "[*] Fetching updates since $since...\n";

        $employees = $adapter->syncEmployees($since);

        echo "[+] Found " . count($employees) . " updated records.\n";

        foreach ($employees as $emp) {
            echo "    -> Importing: {$emp['first_name']} {$emp['last_name']} ({$emp['role']})... OK\n";
        }

        echo "[+] Synchronization Complete.\n";
    } else {
        echo "[!] Connection Failed (Auth Error).\n";
    }

} catch (Exception $e) {
    echo "[!] Critical Error: " . $e->getMessage() . "\n";
}
