<?php

test('error 404 displays custom template', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/non-esiste');
    $exception = new \Slim\Exception\HttpNotFoundException($request);

    $handler = new \MCAG\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/non-esiste');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(404);
    $body = (string) $response->getBody();
    expect($body)->toContain('Pagina Non Trovata');
    expect($body)->toContain('404');
});
