<?php
require __DIR__ . '/../vendor/autoload.php';

use FratellanzaMilitare\Service\AI\DocumentIngestionService;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Build Container
$containerBuilder = new ContainerBuilder();
$definitions = require __DIR__ . '/../config/container.php';
foreach ($definitions as $def) {
    if (file_exists($def)) {
        $containerBuilder->addDefinitions($def);
    }
}
$container = $containerBuilder->build();

echo "🚀 Ingesting CHANGELOG.md into AI Knowledge Base...\n";

try {
    $ingestionService = $container->get(DocumentIngestionService::class);
    $targetFile = __DIR__ . '/../CHANGELOG.md';

    if (!file_exists($targetFile)) {
        die("❌ CHANGELOG.md not found!");
    }

    echo "Reading file: $targetFile\n";
    $ingestionService->ingest($targetFile, ['category' => 'documentation', 'title' => 'Changelog System v5.2']);

    echo "✅ Successfully Ingested CHANGELOG.md\n";

    // Also Ingest Decision Log
    $decisionLog = __DIR__ . '/../Documentazione/Architettura/2026-01-13_DECISION_LOG.md';
    if (file_exists($decisionLog)) {
        echo "Reading file: $decisionLog\n";
        $ingestionService->ingest($decisionLog, ['category' => 'architecture', 'title' => 'Decision Log']);
        echo "✅ Successfully Ingested DECISION_LOG.md\n";
    }

    // Ingest Commercial Benchmark Report
    $benchmarkReport = __DIR__ . '/../Documentazione/Analisi/REPORT_COMMERCIALE_BENCHMARK_2026.md';
    if (file_exists($benchmarkReport)) {
        echo "Reading file: $benchmarkReport\n";
        $ingestionService->ingest($benchmarkReport, ['category' => 'analysis', 'title' => 'Commercial Benchmark 2026']);
        echo "✅ Successfully Ingested REPORT_COMMERCIALE_BENCHMARK_2026.md\n";
    }

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
