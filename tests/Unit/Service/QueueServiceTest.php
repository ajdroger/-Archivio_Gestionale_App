<?php

namespace Tests\Unit\Service;

use MCAG\Jobs\JobInterface;
use MCAG\Service\QueueService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class QueueServiceTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;
    private $queueService;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->queueService = new QueueService($this->pdoMock);
    }

    public function testPushInsertsJob()
    {
        $jobMock = $this->createMock(JobInterface::class);
        $jobMock->method('getPayload')->willReturn(['data' => 'test']);
        $jobMock->method('getQueue')->willReturn('default');

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->assertTrue($this->queueService->push($jobMock));
    }

    public function testPopReturnsNullIfNoJob()
    {
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute');

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false); // No job found

        $this->assertNull($this->queueService->pop());
    }

    public function testPopReturnsJobAndReservesIt()
    {
        $jobData = [
            'id' => 1,
            'queue' => 'default',
            'payload' => json_encode(['data' => 'test']),
            'attempts' => 0
        ];

        // Expect preparation of SELECT and UPDATE
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        // First execute (SELECT), Second execute (UPDATE)
        $this->stmtMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn($jobData);

        $result = $this->queueService->pop();

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals(['data' => 'test'], $result['payload']);
    }

    public function testCompleteDeletesJob()
    {
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM jobs'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with(['id' => 123])
            ->willReturn(true);

        $this->assertTrue($this->queueService->complete(123));
    }
}
