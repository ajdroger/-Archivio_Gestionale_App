<?php

test('error 503 displays custom template', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/maintenance');
    // Slim 4 standardly doesn't have a 503 exception class, so we simulate it
    $exception = new class ($request) extends \Slim\Exception\HttpException {
        public function __construct($request)
        {
            parent::__construct($request, 'Service Unavailable', 503);
            $this->title = 'Service Unavailable';
            $this->description = 'The server is currently unavailable (because it is overloaded or down for maintenance).';
        }
    };

    $handler = new \FratellanzaMilitare\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/maintenance');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(503);
    $body = (string) $response->getBody();
    expect($body)->toContain('Servizio Non Disponibile');
});
