<?php

test('error 500 displays custom template', function () {
    // Un'eccezione generica (RuntimeException) viene trattata come 500 dal GlobalExceptionHandler
    $exception = new \RuntimeException('Qualcosa è andato storto!');

    $handler = new \FratellanzaMilitare\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/crash');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(500);
    $body = (string) $response->getBody();
    expect($body)->toContain('Errore del Server');
    expect($body)->toContain('Qualcosa è andato storto!');
});
