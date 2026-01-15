<?php

use MCAG\Controller\HomeController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use PHPUnit\Framework\TestCase;

class DashboardGeniusTest extends TestCase
{
    private $controller;
    private $responseFactory;
    private $requestFactory;

    protected function setUp(): void
    {
        // Mock dependencies
        $mustache = $this->createMock(\Mustache_Engine::class);
        $repo = $this->createMock(\MCAG\GestioneSoci\SocioRepository::class);
        $resilience = $this->createMock(\MCAG\Debug\ResilienceMonitor::class);
        $health = $this->createMock(\MCAG\Service\HealthCheckService::class);
        $config = $this->createMock(\MCAG\Service\ConfigurationService::class);

        // Repo should return basic stats
        $repo->method('getStatistics')->willReturn([
            'total' => 100,
            'active' => 80
        ]);

        // Mustache should render and capture data passed to it
        $mustache->expects($this->once())
            ->method('render')
            ->willReturnCallback(function ($template, $data) {
                // We will verify the data in the return value or by capturing it here
                // For simplicity, we just return a JSON encoding of data to verify content string
                return json_encode($data);
            });

        $this->controller = new HomeController($mustache, $repo, $resilience, $health, $config);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testDashboardContainsGeniusModeMockData()
    {
        // Arrange
        // Admin user in session
        $_SESSION['username'] = 'Aj_GodMode';
        $_SESSION['user_role'] = 'admin';

        $request = $this->requestFactory->createServerRequest('GET', '/admin/dashboard');
        $response = $this->responseFactory->createResponse();

        // Act
        $result = $this->controller->dashboard($request, $response);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        // Assert
        // 1. DEFCON
        $this->assertArrayHasKey('defcon_level', $data);
        $this->assertEquals(5, $data['defcon_level']);

        // 2. Threat Map (JSON string)
        $this->assertArrayHasKey('threat_map', $data);
        $threats = json_decode($data['threat_map'], true);
        $this->assertIsArray($threats);
        $this->assertCount(3, $threats);
        $this->assertEquals('ddos', $threats[0]['type']);

        // 3. Neural Logs
        $this->assertArrayHasKey('neural_logs', $data);
        $this->assertCount(4, $data['neural_logs']);
        $this->assertEquals('CORTEX', $data['neural_logs'][0]['module']);

        // 4. Financial Tickers
        $this->assertArrayHasKey('financial_tickers', $data);
        $this->assertGreaterThanOrEqual(4, count($data['financial_tickers']));
        $this->assertEquals('MCAG.AS', $data['financial_tickers'][0]['symbol']);

        // 5. Voice Logs (JSON)
        $this->assertArrayHasKey('voice_logs', $data);
        $voice = json_decode($data['voice_logs'], true);
        $this->assertEquals('System Check', $voice[0]['cmd']);
    }
}
