<?php
require __DIR__ . '/../../vendor/autoload.php';

use Predis\Client;

echo "--- Redis Connection Check ---\n";

$host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
$port = $_ENV['REDIS_PORT'] ?? 6379;

echo "Target: $host:$port\n";

try {
    if (!class_exists(Client::class)) {
        throw new Exception("Predis library not installed (run composer require predis/predis)");
    }

    $client = new Client([
        'scheme' => 'tcp',
        'host' => $host,
        'port' => $port,
        'timeout' => 2.0
    ]);

    echo "Connecting...\n";
    $client->connect();

    if ($client->isConnected()) {
        echo "[OK] Connected!\n";
        echo "Server: " . $client->info()['Server']['redis_version'] . "\n";

        $key = 'debug_test_key_' . time();
        $client->set($key, 'working');
        echo "[OK] Write Test (SET $key)\n";

        $val = $client->get($key);
        echo "[OK] Read Test: $val\n";

        $client->del($key);
        echo "[OK] Delete Test\n";
    } else {
        echo "[FAIL] Could not connect (unknown reason)\n";
    }

} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo "Note: If you don't have Redis installed, this is expected.\n";
}
