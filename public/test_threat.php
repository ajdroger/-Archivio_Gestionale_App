<?php
require __DIR__ . '/../vendor/autoload.php';

use MCAG\SecurityLayer\AuditTrail;
use DI\ContainerBuilder;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Bootstrap
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$containerBuilder = new ContainerBuilder();
foreach ((require __DIR__ . '/../config/container.php') as $definitions) {
    $containerBuilder->addDefinitions($definitions);
}
$container = $containerBuilder->build();

$audit = AuditTrail::getInstance();
$audit->setPdo($container->get(PDO::class));
$audit->setLogger($container->get('audit_logger'));

echo "Attempting to log a test threat...<br>";

try {
    $audit->logEvento(null, 'LOGIN_FAILED', 'MANUAL_TEST_INJECTION');
    echo "Log command executed.<br>";
} catch (\Exception $e) {
    echo "Log command FAILED: " . $e->getMessage() . "<br>";
}

echo "Checking the last 5 logs:<br>";
$threats = $audit->getThreats(5);
echo "<pre>";
print_r($threats);
echo "</pre>";
