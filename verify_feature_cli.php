<?php
require __DIR__ . '/vendor/autoload.php';

// 1. Setup Environment & Container
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder = new \DI\ContainerBuilder();
foreach ((require __DIR__ . '/config/container.php') as $definitions) {
    $containerBuilder->addDefinitions($definitions);
}
$container = $containerBuilder->build();

// Mock dependencies
$mustache = $container->get(Mustache_Engine::class);
$repo = $container->get(\MCAG\GestioneSoci\SocioRepository::class);
$resilience = $container->get(\MCAG\Debug\ResilienceMonitor::class);
$health = $container->get(\MCAG\Service\HealthCheckService::class);
$config = $container->get(\MCAG\Service\ConfigurationService::class);

// Initialize AuditTrail Singleton
$auditTrail = \MCAG\SecurityLayer\AuditTrail::getInstance();
$auditTrail->setPdo($container->get(PDO::class));

// 2. Instantiate Controller
$controller = new \MCAG\Controller\HomeController(
    $mustache,
    $repo,
    $resilience,
    $health,
    $config,
    $auditTrail // Injected
);

// 3. Mock Request/Response
$request = \Slim\Psr7\Factory\ServerRequestFactory::createFromGlobals();
$response = new \Slim\Psr7\Response();

// 4. Insert Test Data directly
$pdo = $container->get(PDO::class);
$stmt = $pdo->prepare("INSERT INTO traffic_logs (ip_address, method, path, status_code, user_agent, risk_level, threat_score, geodata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute(['1.2.3.4', 'GET', '/cli-test-vector', 200, 'CLI-Agent', 'CRITICAL', 99, json_encode(['lat' => 45.0, 'lon' => 9.0])]);
$lastId = $pdo->lastInsertId();

echo "Inserted Test Log ID: $lastId\n";

// 5. Call API Method
$result = $controller->securityStats($request, $response);
$body = (string) $result->getBody();

// 6. Verify Output
$data = json_decode($body, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "FAILED: Invalid JSON output.\n";
    echo $body;
    exit(1);
}

$found = false;
foreach ($data as $item) {
    if ($item['ip'] === '1.2.3.4' && $item['msg'] === 'GET /cli-test-vector') {
        $found = true;
        echo "SUCCESS: Found injected threat vector in API response.\n";
        echo "Details: " . json_encode($item['details']) . "\n";
        break;
    }
}

if (!$found) {
    echo "FAILED: Test log not found in API response.\n";
    print_r($data);
    exit(1);
}

// Cleanup
$pdo->exec("DELETE FROM traffic_logs WHERE id = $lastId");
echo "Test Cleaned Up.\n";
