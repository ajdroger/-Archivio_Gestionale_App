<?php

namespace Tests\Feature\Controller;

use FratellanzaMilitare\Controller\SettingsController;
use Mustache_Engine;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class SettingsControllerTest extends TestCase
{
    private $mustacheMock;
    private $settingsController;

    protected function setUp(): void
    {
        $this->mustacheMock = $this->createMock(Mustache_Engine::class);
        $this->settingsController = new SettingsController($this->mustacheMock);

        // Simulate Session
        // Simulate Session
        $_SESSION['user_role'] = 'admin';
        $_SESSION['username'] = 'admin';
        $_SESSION['user_id'] = 1;
    }

    protected function tearDown(): void
    {
        unset($_SESSION['user_role']);
        unset($_SESSION['username']);
        unset($_SESSION['user_id']);
    }

    public function testViewRendersSettingsTemplate()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        // Mock RouteParser
        $routeParserMock = $this->createMock(\Slim\Interfaces\RouteParserInterface::class);
        $routeParserMock->method('urlFor')->willReturn('/mock-url');

        // Mock RoutingResults
        $routingResultsMock = $this->createMock(\Slim\Routing\RoutingResults::class);

        // Configure Request attributes expected by RouteContext
        $requestMock->method('getAttribute')
            ->willReturnMap([
                [\Slim\Routing\RouteContext::ROUTE_PARSER, null, $routeParserMock],
                [\Slim\Routing\RouteContext::ROUTING_RESULTS, null, $routingResultsMock],
                ['flash_success', null, null],
                ['flash_error', null, null]
            ]);

        // Expect Mustache render call
        $this->mustacheMock->expects($this->once())
            ->method('render')
            ->with('impostazioni', $this->callback(function ($data) {
                return $data['title'] === 'Impostazioni Profilo'
                    && $data['user']['username'] === 'admin'
                    && $data['user_initial'] === 'A';
            }))
            ->willReturn('<html>Settings Page</html>');

        // Expect Response Body Write
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $streamMock->expects($this->once())
            ->method('write')
            ->with('<html>Settings Page</html>');

        $responseMock->method('getBody')->willReturn($streamMock);

        $result = $this->settingsController->view($requestMock, $responseMock);

        $this->assertSame($responseMock, $result);
    }
}
