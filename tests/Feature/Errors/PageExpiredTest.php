<?php

test('error 419 displays custom template', function () {
    // 419 non è standard PSR-7/Slim ma spesso usato per CSRF
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('POST', '/form');
    // Simuliamo lanciando un'eccezione generica con codice 419 o estendendo HttpException
    $exception = new class ($request) extends \Slim\Exception\HttpException {
        protected $code = 419;
        protected $message = 'Page Expired';
        protected $title = 'Page Expired';
        protected $description = 'Page Expired';
    };

    $handler = new \FratellanzaMilitare\Debug\GlobalExceptionHandler(
        $this->app->getContainer()->get(\Psr\Log\LoggerInterface::class),
        $this->app->getContainer()->get(\Mustache_Engine::class)
    );

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('POST', '/form');
    $request = $request->withHeader('Accept', 'text/html');

    $response = $handler(
        $request,
        $exception,
        true,
        true,
        true
    );

    expect($response->getStatusCode())->toBe(419);
    $body = (string) $response->getBody();
    expect($body)->toContain('Pagina Scaduta');
});
