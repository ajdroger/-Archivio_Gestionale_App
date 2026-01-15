<?php

use MCAG\Controller\Intelligence\StatsDashboardController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;


class FinancialIntelTest extends \PHPUnit\Framework\TestCase
{
    private $controller;
    private $mustache;
    private $repo;
    private $monitor;
    private $health;

    protected function setUp(): void
    {
        // Mock Session
        $_SESSION['user_role'] = 'admin';
        $_SESSION['username'] = 'admin';

        // Mock Dependencies
        $this->mustache = $this->createMock(Mustache_Engine::class);
        $this->repo = $this->createMock(\MCAG\GestioneSoci\SocioRepository::class);
        $this->monitor = $this->createMock(\MCAG\Debug\ResilienceMonitor::class);
        $this->health = $this->createMock(\MCAG\Service\HealthCheckService::class);

        // StatsDashboardController instantiation
        $this->controller = new StatsDashboardController(
            $this->mustache,
            $this->repo,
            $this->monitor,
            $this->health
        );
    }

    public function test_financial_methods_exist()
    {
        // Reflection to verify private methods exist
        $reflector = new ReflectionClass(StatsDashboardController::class);

        $this->assertTrue($reflector->hasMethod('getFinancialProjections'));
        $this->assertTrue($reflector->hasMethod('getAssetValuation'));
        $this->assertTrue($reflector->hasMethod('getMarketTicker'));
    }

    public function test_view_receives_financial_data()
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/admin/statistiche');
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        // Expectation: Mustache render should be called with FIU keys
        $this->mustache->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('admin/statistics'),
                $this->callback(function ($viewData) {
                    return isset($viewData['fin_projections'])
                        && isset($viewData['asset_valuations'])
                        && isset($viewData['market_ticker']);
                })
            )
            ->willReturn('<html>Mocked FIU HTML</html>');

        $this->controller->view($request, $response);
    }
}
