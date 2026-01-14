<?php

use MCAG\Controller\Intelligence\StatsDashboardController;
use MCAG\GestioneSoci\SocioRepository;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

test('statistics view renders', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(Mustache_Engine::class);
    $mustache->expects($this->once())
        ->method('render')
        ->willReturn('Stats HTML');

    $repo = $this->createMock(SocioRepository::class);
    $repo->expects($this->once())
        ->method('getStatistics')
        ->willReturn(['total' => 10]);

    $resilience = $this->createMock(\MCAG\Debug\ResilienceMonitor::class);
    $health = $this->createMock(\MCAG\Service\HealthCheckService::class);

    $controller = new StatsDashboardController($mustache, $repo, $resilience, $health);

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/stats');
    $response = (new ResponseFactory())->createResponse();

    $result = $controller->view($request, $response);

    expect($result->getStatusCode())->toBe(200);
    $response->getBody()->rewind();
    expect($response->getBody()->getContents())->toBe('Stats HTML');
});
