<?php

use FratellanzaMilitare\Middleware\SecurityHeadersMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

test('security headers are added', function () {
    $middleware = new SecurityHeadersMiddleware();

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
    $responseFactory = new ResponseFactory();
    $response = $responseFactory->createResponse();

    $handler = new class ($response) implements RequestHandlerInterface {
        private $response;
        public function __construct($response)
        {
            $this->response = $response;
        }
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return $this->response;
        }
    };

    $newResponse = $middleware->process($request, $handler);

    expect($newResponse->hasHeader('Content-Security-Policy'))->toBeTrue();
    expect($newResponse->hasHeader('X-Frame-Options'))->toBeTrue();
    expect($newResponse->getHeaderLine('X-Frame-Options'))->toBe('DENY');
});
