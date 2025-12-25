<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestIdMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE_NAME = 'request_id';
    public const HEADER_NAME = 'X-Request-ID';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Genera un ID unico se non presente (es. se non passato da un reverse proxy)
        $requestId = $request->getHeaderLine(self::HEADER_NAME) ?: bin2hex(random_bytes(8));

        // Espone l'ID in $_SERVER così che Monolog possa recuperarlo nel Processor
        $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;

        // Aggiunge l'ID agli attributi della richiesta per facilitarne il recupero
        $request = $request->withAttribute(self::ATTRIBUTE_NAME, $requestId);

        // Prosegue l'esecuzione
        $response = $handler->handle($request);

        // Aggiunge l'ID alla risposta per facilitare il debugging lato client
        return $response->withHeader(self::HEADER_NAME, $requestId);
    }
}
