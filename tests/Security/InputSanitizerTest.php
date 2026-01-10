<?php

use FratellanzaMilitare\Middleware\InputSanitizerMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

test('InputSanitizerMiddleware pulisce gli input da script malevoli', function () {
    $purifierConfig = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($purifierConfig);
    $middleware = new InputSanitizerMiddleware($purifier);

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/test');
    $dirtyData = [
        'nome' => 'Mario <script>alert("XSS")</script>',
        'cognome' => 'Rossi',
        'descrizione' => '<b>Testo grassetto</b> sicuro.'
    ];
    $request = $request->withParsedBody($dirtyData);

    $handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
        public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            $response = (new ResponseFactory())->createResponse();
            $response->getBody()->write(json_encode($request->getParsedBody()));
            return $response;
        }
    };

    $response = $middleware->process($request, $handler);
    $body = json_decode((string) $response->getBody(), true);

    expect($body['nome'])->toBe('Mario '); // Script rimosso
    expect($body['cognome'])->toBe('Rossi');
    expect($body['descrizione'])->toBe('<b>Testo grassetto</b> sicuro.'); // HTML sicuro mantenuto
});

test('InputSanitizerMiddleware ignora i campi password', function () {
    $purifierConfig = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($purifierConfig);
    $middleware = new InputSanitizerMiddleware($purifier);

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/login');
    $sensitiveData = [
        'username' => '<script>alert("XSS")</script>',
        'password' => 'super<secret>password',
        'password_confirmation' => 'super<secret>password'
    ];
    $request = $request->withParsedBody($sensitiveData);

    $handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
        public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            $response = (new ResponseFactory())->createResponse();
            $response->getBody()->write(json_encode($request->getParsedBody()));
            return $response;
        }
    };

    $response = $middleware->process($request, $handler);
    $body = json_decode((string) $response->getBody(), true);

    expect($body['username'])->toBe(''); // Script rimosso
    expect($body['password'])->toBe('super<secret>password'); // Password non toccata
    expect($body['password_confirmation'])->toBe('super<secret>password');
});
