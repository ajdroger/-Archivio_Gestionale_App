<?php

/**
 * Script per generare SBOM (Software Bill of Materials)
 * Output: sbom.json
 */

require __DIR__ . '/../../vendor/autoload.php';

$lockFile = __DIR__ . '/../../composer.lock';

if (!file_exists($lockFile)) {
    die("Error: composer.lock not found.\n");
}

$data = json_decode(file_get_contents($lockFile), true);
$packages = $data['packages'] ?? [];

$sbom = [
    'bomFormat' => 'CycloneDX',
    'specVersion' => '1.4',
    'version' => 1,
    'components' => []
];

foreach ($packages as $pkg) {
    $sbom['components'][] = [
        'type' => 'library',
        'name' => $pkg['name'],
        'version' => $pkg['version'],
        'description' => $pkg['description'] ?? '',
        'licenses' => array_map(fn($l) => ['license' => ['id' => $l]], $pkg['license'] ?? []),
        'purl' => "pkg:composer/{$pkg['name']}@{$pkg['version']}"
    ];
}

$outputPath = __DIR__ . '/../../sbom.json';
file_put_contents($outputPath, json_encode($sbom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "SBOM generated at $outputPath\n";

