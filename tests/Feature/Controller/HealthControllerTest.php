<?php

namespace Tests\Feature\Controller;

use MCAG\Controller\HealthController;
use MCAG\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class HealthControllerTest extends TestCase
{
    private $healthServiceMock;
    private $healthController;

    protected function setUp(): void
    {
        $this->healthServiceMock = $this->createMock(HealthCheckService::class);
        $this->healthController = new HealthController($this->healthServiceMock);
    }

    public function testCheckReturns200WhenHealthy()
    {
        // Mock Healthy Response
        $this->healthServiceMock->expects($this->once())
            ->method('checkAll')
            ->willReturn(['status' => 'healthy', 'db' => 'ok']);

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        // Expect Response Setup
        $responseMock->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $responseMock->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();

        $responseMock->method('getBody')->willReturn($streamMock);

        $streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode(['status' => 'healthy', 'db' => 'ok'], JSON_PRETTY_PRINT));

        $this->healthController->check($requestMock, $responseMock);
    }

    public function testCheckReturns503WhenUnhealthy()
    {
        // Mock Unhealthy Response
        $this->healthServiceMock->expects($this->once())
            ->method('checkAll')
            ->willReturn(['status' => 'unhealthy', 'error' => 'DB down']);

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $responseMock->expects($this->once())->method('withHeader')->willReturnSelf();

        $responseMock->expects($this->once())
            ->method('withStatus')
            ->with(503)
            ->willReturnSelf();

        $responseMock->method('getBody')->willReturn($streamMock);

        $this->healthController->check($requestMock, $responseMock);
    }
}
