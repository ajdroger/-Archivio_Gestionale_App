<?php

require __DIR__ . '/vendor/autoload.php';

// Mock Environment for Middleware Test
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/public/v1/test?hack=UNION%20SELECT'; // Score +50
$_SERVER['HTTP_USER_AGENT'] = 'curl/7.68.0'; // Score +20
// Need +21 more... let's add some XSS
$_SERVER['QUERY_STRING'] = 'hack=UNION%20SELECT&xss=<script>'; // Score +40 (Total: 110)

echo "--- SENTINEL MODE SIMULATION ---\n";

// 0. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 1. Setup DI Container
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/config/definitions/core.php');
$containerBuilder->addDefinitions(__DIR__ . '/config/definitions/devtools.php'); // Contains FirewallOps
$container = $containerBuilder->build();

// 2. Mock PDO (We don't want to actually write to DB in this test script if possible, or we use real DB)
// Using Real DB for integration test
$pdo = $container->get(PDO::class);

// 3. Mock FirewallOps (Vital: We do NOT want to ban ourselves or the server in a test)
// overload the definition
$firewallMock = new class (__DIR__) extends \MCAG\SecurityLayer\Arsenal\FirewallOps {
    public function banIp(string $ip): bool
    {
        echo "[SENTINEL] Intercepted BAN command for IP: $ip\n";
        return true;
    }
};

// 4. Instantiate Middleware manually (with debug override)
$middleware = new class ($pdo, $firewallMock) extends \MCAG\Middleware\TrafficSurveillanceMiddleware {
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Slim\Psr7\Response
    {
        echo "[DEBUG] Middleware Invoked\n";
        echo "[DEBUG] Params: " . print_r($request->getQueryParams(), true) . "\n";
        return parent::__invoke($request, $handler);
    }
// We can't easily override private methods or internal logic without reflection or editing source.
// So we'll trust the parent logic but verify inputs.
};

// 5. Create Mock Request/Handler
$uri = (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/api/public/v1/test?q=<script>');
$request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', $uri)
    ->withHeader('User-Agent', 'curl/7.68.0') // Essential for Bot Detection (+20)
    ->withQueryParams([
        'hack' => 'UNION SELECT', // +50
        'xss' => '<script>',      // +40
        'admin' => '../wp-admin'  // +30  -> Total: 120 + 20 (curl) = 140
    ]);

$handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        return (new \Slim\Psr7\Factory\ResponseFactory())->createResponse(200)->withBody((new \Slim\Psr7\Stream(fopen('php://memory', 'r+'))));
    }
};

// 6. Execute
echo "Executing High-Threat Request...\n";
$response = $middleware($request, $handler);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Body: " . (string) $response->getBody() . "\n";

if ($response->getStatusCode() === 403) {
    echo "\n[PASS] Sentinel Mode correctly blocked the threat.\n";
} else {
    echo "\n[FAIL] Sentinel Mode did NOT block the threat.\n";
}
