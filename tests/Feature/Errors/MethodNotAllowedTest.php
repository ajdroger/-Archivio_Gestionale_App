<?php

test('error 405 displays custom template', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('POST', '/login');
    $exception = new \Slim\Exception\HttpMethodNotAllowedException($request);

    $handler = new \MCAG\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('POST', '/login'); // Ipotesi
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(405);
    $body = (string) $response->getBody();
    expect($body)->toContain('Metodo Non Consentito');
});
