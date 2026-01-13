<?php

namespace Tests\Unit\Controller\DevTools;

use MCAG\Controller\DevTools\DevToolsDatabaseController;
use Mustache_Engine;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class DevToolsDatabaseControllerTest extends TestCase
{
    private $pdoMock;
    private $controller;
    private $mustacheMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->mustacheMock = $this->createMock(Mustache_Engine::class);

        $this->controller = new DevToolsDatabaseController($this->mustacheMock);
        $this->controller->setConnection($this->pdoMock);
    }

    public function testDbQueryExecutesSelect()
    {
        $sql = 'SELECT * FROM users';
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Test']]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmtMock);

        $stmtMock->expects($this->once())->method('execute');

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getParsedBody')->willReturn(['sql' => $sql]);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        $streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode(['results' => [['id' => 1, 'name' => 'Test']]]));

        $this->controller->dbQuery($requestMock, $responseMock);
    }

    public function testDbQueryExecutesUpdate()
    {
        $sql = 'UPDATE users SET name="A"';
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('rowCount')->willReturn(5);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmtMock);

        $stmtMock->expects($this->once())->method('execute');

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getParsedBody')->willReturn(['sql' => $sql]);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        $streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode(['results' => [['message' => 'Query eseguita.', 'rows_affected' => 5]]]));

        $this->controller->dbQuery($requestMock, $responseMock);
    }
}
