<?php
// debug_di_full.php

use DI\ContainerBuilder;
use MCAG\Controller\External\WorkshiftController;

require __DIR__ . '/vendor/autoload.php';

echo "=== START DEBUG DI ===\n";

// 1. Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo "Environment loaded.\n";

try {
    // 2. Build Container (Exact copy of index.php logic)
    $containerBuilder = new ContainerBuilder();
    $definitionsFiles = require __DIR__ . '/config/container.php';

    echo "Files to load:\n";
    foreach ($definitionsFiles as $file) {
        echo " - " . basename($file) . "\n";
        $containerBuilder->addDefinitions($file);
    }

    $container = $containerBuilder->build();
    echo "Container built successfully.\n";

    // 3. Test Retrieval
    echo "Attempting to get WorkshiftController...\n";
    if ($container->has(WorkshiftController::class)) {
        $controller = $container->get(WorkshiftController::class);
        echo "SUCCESS! Controller instantiated.\n";

        // SIMULATE REQUEST
        echo "Simulating applyAiSuggestion request...\n";

        // Mock Request
        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/api/workshift/apply-ai');
        $request = $request->withParsedBody(['target_date' => date('Y-m-d')]); // Valid payload

        // Mock Response
        $response = new \Slim\Psr7\Response();

        // Call Method
        try {
            $result = $controller->applyAiSuggestion($request, $response);
            echo "Method executed. Status: " . $result->getStatusCode() . "\n";
            echo "Body: " . (string) $result->getBody() . "\n";
        } catch (\Throwable $e) {
            echo "CRITICAL ERROR during execution:\n";
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
        }

    } else {
        echo "ERROR: WorkshiftController not found in container.\n";
    }

} catch (\Throwable $e) {
    echo "\n!!! CRITICAL ERROR !!!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
