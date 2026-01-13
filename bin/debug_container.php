<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

try {
    $containerBuilder = new ContainerBuilder();
    $root = __DIR__ . '/..';

    $definitions = require $root . '/config/container.php';
    foreach ($definitions as $def) {
        $containerBuilder->addDefinitions($def);
    }

    $container = $containerBuilder->build();
    echo "Container built successfully.\n";

    // Test resolution of critical services
    $container->get(\Psr\Log\LoggerInterface::class);
    echo "Logger resolved.\n";

    $container->get(\Mustache_Engine::class);
    echo "Mustache resolved.\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

