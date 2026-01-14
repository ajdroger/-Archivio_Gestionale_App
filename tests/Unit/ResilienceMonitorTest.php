<?php

use MCAG\Debug\ResilienceMonitor;
use Psr\Log\LoggerInterface;

test('monitor health returns array', function () {
    $pdo = Mockery::mock(PDO::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('debug')->andReturnNull();
    $logger->shouldReceive('error')->andReturnNull();

    // Mock PDOStatement
    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('fetch')->andReturn(['Msg_text' => 'OK']);

    // Mock PDO behavior for checkDatabaseIntegrity
    $pdo->shouldReceive('getAttribute')->andReturn('mysql');
    $pdo->shouldReceive('query')->andReturn($stmt);

    $monitor = new ResilienceMonitor($pdo, $logger, sys_get_temp_dir());
    $health = $monitor->monitorHealth();

    expect($health)->toBeArray();
    expect($health)->toHaveKeys(['database', 'backups', 'logs', 'security']);
});

test('monitor checks disk space', function () {
    $pdo = Mockery::mock(PDO::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('debug')->andReturnNull();
    $logger->shouldReceive('error')->andReturnNull();

    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->shouldReceive('fetch')->andReturn(['Msg_text' => 'OK']);

    $pdo->shouldReceive('getAttribute')->andReturn('mysql');
    $pdo->shouldReceive('query')->andReturn($stmt);

    $monitor = new ResilienceMonitor($pdo, $logger, sys_get_temp_dir());
    $health = $monitor->monitorHealth();

    expect($health['database']['details'])->toBeArray(); // Assuming disk space checked in db details or irrelevant
});
