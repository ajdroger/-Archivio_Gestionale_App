<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use DI\ContainerBuilder;
use FratellanzaMilitare\Queue\QueueInterface;

echo "--- Debug Trace Start ---\n";

$builder = new ContainerBuilder();
$definitions = require __DIR__ . '/../config/container.php';

foreach ($definitions as $file) {
    echo "Loading: $file\n";
    $builder->addDefinitions($file);
}

try {
    $container = $builder->build();
    echo "Container built.\n";

    echo "Checking PDO...\n";
    if ($container->has(\PDO::class)) {
        echo "PDO is set.\n";
    } else {
        echo "PDO MISSING!\n";
    }

    echo "Resolving QueueInterface...\n";
    $queue = $container->get(QueueInterface::class);
    echo "QueueInterface resolved: " . get_class($queue) . "\n";

} catch (\Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
