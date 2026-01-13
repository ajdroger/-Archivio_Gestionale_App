<?php

use MCAG\Middleware\RequestIdMiddleware;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

test('request id is logged in context', function () {
    // 1. Setup Logger with the same processor as container.php
    $handler = new TestHandler();
    $logger = new Logger('test');
    $logger->pushHandler($handler);
    $logger->pushProcessor(function ($record) {
        if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
            $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'];
        }
        return $record;
    });

    // 2. Setup Middleware
    $middleware = new RequestIdMiddleware();

    // 3. Create Mock Request & Handler
    $request = \Slim\Psr7\Factory\ServerRequestFactory::createFromGlobals();
    $handlerMock = new class ($logger) implements \Psr\Http\Server\RequestHandlerInterface {
        private LoggerInterface $logger;

        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
        }

        public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            // Simulate Controller Action logging
            $this->logger->info("Test Message");
            return new \Slim\Psr7\Response();
        }
    };

    // 4. Run Middleware
    $response = $middleware->process($request, $handlerMock);

    // 5. Verify Response Header
    $requestId = $response->getHeaderLine('X-Request-ID');
    expect($requestId)->not->toBeEmpty();

    // 6. Verify Log Record
    $records = $handler->getRecords();
    expect($records)->toHaveCount(1);
    expect($records[0]['extra']['request_id'])->toBe($requestId);
});
