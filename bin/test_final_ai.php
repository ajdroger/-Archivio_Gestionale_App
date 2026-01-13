<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use DI\ContainerBuilder;
use FratellanzaMilitare\Controller\AI\AssistantController;

echo "--- Final Diagnostic Start ---\n";

// 1. Build Container logic (mimicking index.php)
$builder = new ContainerBuilder();
foreach ((require __DIR__ . '/../config/container.php') as $definitions) {
    // echo "Loading: $definitions\n";
    $builder->addDefinitions($definitions);
}
$container = $builder->build();

// 2. Test AssistantController Resolution
try {
    echo "Resolving AssistantController...\n";
    $controller = $container->get(AssistantController::class);
    echo "✅ AssistantController instantiated successfully.\n";
} catch (\Throwable $e) {
    echo "❌ FATAL: AssistantController Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test Dependencies inside
try {
    $llm = $container->get(\FratellanzaMilitare\AI\Providers\OllamaProvider::class);
    echo "✅ OllamaProvider resolved.\n";

    $store = $container->get(\FratellanzaMilitare\AI\RAG\SimpleVectorStore::class);
    echo "✅ SimpleVectorStore resolved.\n";

    $queue = $container->get(\FratellanzaMilitare\Queue\QueueInterface::class);
    echo "✅ QueueInterface resolved.\n";
} catch (\Throwable $e) {
    echo "❌ Dependency Check Failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "--- All Checks Passed ---\n";
