<?php

require __DIR__ . '/vendor/autoload.php';

// Mock Environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/security/engage';

// Manual Dependency Construction (Bypassing DI Container for Speed/Isolation)
// This verifies the logical flow of the controller and its tools.

echo "--- TESTING WARFARE ARSENAL ---\n";

$projectRoot = __DIR__;
$firewall = new \MCAG\SecurityLayer\Arsenal\FirewallOps($projectRoot);
$intel = new \MCAG\SecurityLayer\Arsenal\IntelProbe();
$tarpit = new \MCAG\SecurityLayer\Arsenal\Tarpit();

// Mock AuditTrail (Since it requires DB/PDO which is complex to mock here without DI)
// We'll just create a dummy anonymous class or use the real one if singleton works?
// Let's rely on the real one's getInstance but suppress errors if DB fails.
// Actually, let's just make a mock for the controller.
$auditMock = new class extends \MCAG\SecurityLayer\AuditTrail {
    public function __construct()
    {
    }
    public function resolveThreat(int $id): bool
    {
        echo "   [Audit] Threat Neutralized (Mock)\n";
        return true;
    }
};

$controller = new \MCAG\Controller\WarfareController($firewall, $intel, $tarpit, $auditMock);

// Test 1: SCAN
echo "\n[TEST 1] Scanning Target...\n";
$req = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/api/security/engage')
    ->withParsedBody(['action' => 'SCAN', 'ip' => '8.8.8.8']);
$res = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse();

$res = $controller->engageTarget($req, $res);
echo "Status: " . $res->getStatusCode() . "\n";
echo "Body: " . (string) $res->getBody() . "\n";

// Test 2: TRACE
echo "\n[TEST 2] Tracing Target...\n";
$req = $req->withParsedBody(['action' => 'TRACE', 'ip' => '8.8.8.8']);
$res = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse();
$res = $controller->engageTarget($req, $res);
echo "Body: " . (string) $res->getBody() . "\n";

// Test 3: BAN (Simulation)
// We won't actually ban 8.8.8.8 in .htaccess to avoid breaking things, 
// but we check if the method exists.
echo "\n[TEST 3] Firewall Ban Logic...\n";
if (method_exists($firewall, 'banIp')) {
    echo "Firewall capability [OK]. Skipping actual write for safety in test script.\n";
}

echo "\n--- ARSENAL ONLINE ---\n";
