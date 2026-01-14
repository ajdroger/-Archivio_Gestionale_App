<?php

use MCAG\Controller\Admin\DashboardActionController;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Log\LoggerInterface;

// Mock the Logger
$loggerMock = Mockery::mock(LoggerInterface::class);
$loggerMock->shouldReceive('info')->andReturnNull();

// Helper to create the controller with dependencies
function makeController($logger)
{
    return new DashboardActionController($logger);
}

test('toggle config updates setting', function () use ($loggerMock) {
    $controller = makeController($loggerMock);
    $request = (new ServerRequestFactory)->createServerRequest('POST', '/admin/dashboard/toggle')
        ->withParsedBody(['setting' => 'maintenance', 'value' => true]);
    $response = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse();

    $result = $controller->toggleConfig($request, $response);

    expect($result->getStatusCode())->toBe(200);
    $body = json_decode((string) $result->getBody(), true);
    expect($body['success'])->toBeTrue();
    expect($body['new_state'])->toBeTrue();
});

test('toggle config fails without setting', function () use ($loggerMock) {
    $controller = makeController($loggerMock);
    $request = (new ServerRequestFactory)->createServerRequest('POST', '/admin/dashboard/toggle')
        ->withParsedBody(['value' => true]); // Missing 'setting'
    $response = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse();

    $result = $controller->toggleConfig($request, $response);

    expect($result->getStatusCode())->toBe(400);
});

test('send broadcast sends to target', function () use ($loggerMock) {
    $controller = makeController($loggerMock);
    $request = (new ServerRequestFactory)->createServerRequest('POST', '/admin/dashboard/broadcast')
        ->withParsedBody(['target' => 'staff', 'message' => 'Hello Team']);
    $response = (new \Slim\Psr7\Factory\ResponseFactory())->createResponse();

    $result = $controller->sendBroadcast($request, $response);

    expect($result->getStatusCode())->toBe(200);
    $body = json_decode((string) $result->getBody(), true);
    expect($body['success'])->toBeTrue();
});
