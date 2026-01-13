<?php
/**
 * Chaos Monkey Placeholder
 * 
 * Script per simulare guasti randomici in ambiente di staging.
 * NON ESEGUIRE IN PRODUZIONE.
 */

if (getenv('APP_ENV') === 'production') {
    die("Chaos Monkey cannot run in production!\n");
}

echo "Simulating random system failure...\n";

// 1. Random Cache Eviction
$cacheDir = __DIR__ . '/../../storage/cache';
if (is_dir($cacheDir)) {
    $files = glob("$cacheDir/*");
    if (!empty($files)) {
        $randomFile = $files[array_rand($files)];
        if (is_file($randomFile)) {
            unlink($randomFile);
            echo "Chaos: Deleted cache file " . basename($randomFile) . "\n";
        }
    }
}

// 2. Simulate High Latency (Network Partition)
// Sleep between 1 and 3 seconds
$latency = rand(1000000, 3000000);
usleep($latency);
echo "Chaos: Induced latency of " . ($latency / 1000) . "ms\n";
echo "Chaos completed.\n";

