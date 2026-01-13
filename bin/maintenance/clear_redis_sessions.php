<?php
require __DIR__ . '/../../vendor/autoload.php';

use Predis\Client;

echo "--- Redis Session Cleaner ---\n";

$host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
$port = $_ENV['REDIS_PORT'] ?? 6379;

try {
    $client = new Client([
        'scheme' => 'tcp',
        'host' => $host,
        'port' => $port,
    ]);

    $client->connect();

    if ($client->isConnected()) {
        echo "Connected to Redis at $host:$port\n";

        // Find session keys
        $pattern = 'session:*';
        $keys = $client->keys($pattern);
        $count = count($keys);

        echo "Found $count active sessions.\n";

        if ($argc > 1 && $argv[1] === '--force') {
            if ($count > 0) {
                $client->del($keys);
                echo "[OK] Deleted $count sessions.\n";
            } else {
                echo "Nothing to delete.\n";
            }
        } else {
            echo "[INFO] Dry run. Use --force to delete.\n";
        }
    } else {
        echo "[ERR] Could not connect.\n";
    }

} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}

