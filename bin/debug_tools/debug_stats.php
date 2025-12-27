<?php
require dirname(__DIR__) . '/vendor/autoload.php';

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

$cacheFile = __DIR__ . '/var/cache/stats_cache.json';

echo "1. Checking cache file...\n";
if (file_exists($cacheFile)) {
    echo "Found cache file. Deleting...\n";
    unlink($cacheFile);
    echo "Cache file deleted.\n";
} else {
    echo "No cache file found.\n";
}

echo "\n2. Generating new statistics from Repository...\n";
$repo = new PDOSocioRepository();
$stats = $repo->getStatistics();

echo "\n3. Verifying Output:\n";
echo "Totale: " . $stats['totale'] . "\n";
echo "Paganti: " . $stats['paganti'] . "\n";
echo "perc_paganti: " . ($stats['perc_paganti'] ?? 'MISSING') . "%\n";
echo "perc_morosi: " . ($stats['perc_morosi'] ?? 'MISSING') . "%\n";
