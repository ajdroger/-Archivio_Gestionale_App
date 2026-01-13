<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MCAG\Controller\DevTools\DevToolsFileSystemController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

// MOCK SETUP: Create file first because Controller forbids creating new files (is_file check)
file_put_contents(dirname(__DIR__) . '/bin/test_reactor_artifact.txt', 'Initial Content');

// Mock Request for SAVE
$requestFactory = new ServerRequestFactory();
$request = $requestFactory->createServerRequest('POST', '/devtools/fs/save');
$request = $request->withParsedBody([
    'path' => 'bin/test_reactor_artifact.txt',
    'content' => 'Reactor Online. Sensors Active. System Nominal.'
]);

$responseFactory = new ResponseFactory();
$response = $responseFactory->createResponse();

$controller = new DevToolsFileSystemController();

// Execute SAVE
$response = $controller->fsSave($request, $response);
echo "SAVE Status: " . $response->getStatusCode() . "\n";
echo "SAVE Body: " . (string) $response->getBody() . "\n";

// Mock Request for READ
$requestRead = $requestFactory->createServerRequest('POST', '/devtools/fs/read');
$requestRead = $requestRead->withParsedBody([
    'path' => 'bin/test_reactor_artifact.txt'
]);
$responseRead = $responseFactory->createResponse();

// Execute READ
$responseRead = $controller->fsRead($requestRead, $responseRead);
echo "READ Status: " . $responseRead->getStatusCode() . "\n";
echo "READ Body: " . (string) $responseRead->getBody() . "\n";

// CLEANUP
@unlink(dirname(__DIR__) . '/bin/test_reactor_artifact.txt');

