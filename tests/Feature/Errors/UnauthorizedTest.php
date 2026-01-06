<?php

test('error 401 displays custom template', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/protected');
    $exception = new \Slim\Exception\HttpUnauthorizedException($request);

    $handler = new \FratellanzaMilitare\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/protected');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(401);
    $body = (string) $response->getBody();
    expect($body)->toContain('Accesso Non Autorizzato');
    expect($body)->toContain('Accedi');
});
