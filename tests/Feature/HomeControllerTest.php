<?php

use MCAG\Controller\HomeController;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

test('home dashboard renders correctly', function () {
    /** @var \Tests\TestCase $this */
    // Mock Mustache
    $mustache = $this->createMock(Mustache_Engine::class);
    $mustache->expects($this->once())
        ->method('render')
        ->willReturn('<html>Dashboard</html>');
    // Mock Repository
    $repo = $this->createMock(\MCAG\GestioneSoci\SocioRepository::class);
    $repo->expects($this->once())
        ->method('getStatistics')
        ->willReturn(['totale' => 10, 'attivi' => 5, 'morosi' => 5]);

    // Mock ResilienceMonitor
    $resilience = $this->createMock(\MCAG\Debug\ResilienceMonitor::class);

    // Mock HealthCheckService
    $health = $this->createMock(\MCAG\Service\HealthCheckService::class);

    // Mock ConfigurationService
    $config = $this->createMock(\MCAG\Service\ConfigurationService::class);

    $controller = new HomeController($mustache, $repo, $resilience, $health, $config);

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
    $response = (new ResponseFactory())->createResponse();

    $result = $controller->dashboard($request, $response);

    expect($result->getStatusCode())->toBe(200);
    $response->getBody()->rewind();
    expect($response->getBody()->getContents())->toBe('<html>Dashboard</html>');
});
