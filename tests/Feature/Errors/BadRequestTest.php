<?php

test('error 400 displays custom template', function () {
    // Simuliamo una richiesta non valida o costruiamo una response 400 manualmente
    // In Slim, una Bad Request spesso viene lanciata manualmente
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/');
    $exception = new \Slim\Exception\HttpBadRequestException($request);

    // Invochiamo il GlobalExceptionHandler direttamente o tramite un mock di route
    $handler = new \FratellanzaMilitare\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(400);
    $body = (string) $response->getBody();
    expect($body)->toContain('Richiesta Non Valida');
    // expect($body)->toContain('Error 400');
});
