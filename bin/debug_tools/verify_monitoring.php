<?php
require __DIR__ . '/../../vendor/autoload.php';

use MCAG\Controller\DevTools\DevToolsSystemController;

echo ">>> VERIFYING EXPERT MONITORING SUITE <<<\n";

// Mock minimal dependencies for Controller if needed, but it seems to use statics or internal logic mostly.
// Actually it needs DI. Let's try to manually instantiate or use the app container.
// Easier: Just test the logic blocks manually here.

// 1. REDIS TEST
echo "\n[1] Testing Redis Connection...\n";
if (class_exists('Predis\Client')) {
    echo "✔ Predis Class Found.\n";
    try {
        $client = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 1.0,
        ]);
        $client->connect();
        if ($client->isConnected()) {
            $info = $client->info();
            echo "✔ Redis Online! Version: " . $info['Server']['redis_version'] . "\n";
            echo "✔ Memory Used: " . $info['Memory']['used_memory_human'] . "\n";
        } else {
            echo "✘ Redis Not Connected (Offline?)\n";
        }
    } catch (\Exception $e) {
        echo "✘ Redis Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✘ Predis Class NOT Found! Run composer install.\n";
}

// 2. GIT TEST
echo "\n[2] Testing Git Info...\n";
$branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>&1') ?? '');
$hash = trim(shell_exec('git rev-parse --short HEAD 2>&1') ?? '');
if ($branch && strpos($branch, 'fatal') === false) {
    echo "✔ Git Working. Branch: $branch, Hash: $hash\n";
} else {
    echo "✘ Git Error or Not a Repo: $branch\n";
}

// 3. OPCACHE TEST
echo "\n[3] Testing OPCache...\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status) {
        echo "✔ OPCache Enabled.\n";
        echo "✔ Hit Rate: " . round($status['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
    } else {
        echo "✘ OPCache Disabled or No Access.\n";
    }
} else {
    echo "✘ opcache_get_status() function missing.\n";
}

echo "\n>>> VERIFICATION COMPLETE <<<\n";

