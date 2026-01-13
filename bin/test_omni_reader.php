<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use MCAG\Service\AI\DocumentIngestionService;
use MCAG\Service\DocumentParser\DocumentParserFactory;
use MCAG\Service\DocumentParser\WordParserService;
use MCAG\Service\DocumentParser\ExcelParserService;

// Load environment
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

echo "🚀 Testing Omni-Reader Setup...\n\n";

try {
    // 1. Check Factory resolution
    echo "1. Checking DocumentParserFactory resolution... ";
    $factory = $container->get(DocumentParserFactory::class);
    echo "✅ OK (" . get_class($factory) . ")\n";

    // 2. Check Service resolution
    echo "2. Checking DocumentIngestionService resolution... ";
    $service = $container->get(DocumentIngestionService::class);
    echo "✅ OK (" . get_class($service) . ")\n";

    // 3. Verify Parsers instantiation (Manual check)
    echo "3. Verifying Parser Classes via Container...\n";

    $wordParser = $container->get(WordParserService::class);
    echo "  - WordParserService: ✅ OK\n";

    $excelParser = $container->get(ExcelParserService::class);
    echo "  - ExcelParserService: ✅ OK\n";

    $codeParser = $container->get(\MCAG\Service\DocumentParser\CodeParserService::class);
    echo "  - CodeParserService: ✅ OK\n";

    echo "\n🎉 Omni-Reader System is READY!\n";

} catch (\Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

