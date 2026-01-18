<?php
require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $repo = new \MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository(new PDO('sqlite::memory:'));
    echo "Repository loaded successfully.\n";
} catch (Throwable $e) {
    echo "Error loading Repository: " . $e->getMessage() . "\n";
}

try {
    // Mocking dependencies for controller
    $mustache = new Mustache_Engine();
    $repo = new \MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository(new PDO('sqlite::memory:'));

    // Hack strict typing for session if needed, but constructor doesn't use it.
    $controller = new \MCAG\Controller\External\WorkshiftController($mustache, $repo);
    echo "Controller loaded successfully.\n";
} catch (Throwable $e) {
    echo "Error loading Controller: " . $e->getMessage() . "\n";
}
