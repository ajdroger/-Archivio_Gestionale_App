<?php

test('error 403 displays custom template', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/admin');
    $exception = new \Slim\Exception\HttpForbiddenException($request);

    $handler = new \MCAG\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/admin');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(403);
    $body = (string) $response->getBody();
    expect($body)->toContain('Accesso Negato');
    expect($body)->toContain('Non disponi dei permessi');
});
