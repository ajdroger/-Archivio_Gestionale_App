<?php

namespace FratellanzaMilitare\Middleware;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Injects base_url into Mustache parameters for every request.
 */
class BasePathMiddleware implements MiddlewareInterface
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $basePath = (function () {
            if (!isset($_SERVER['SCRIPT_NAME']))
                return '';
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            return $scriptDir === '/' ? '' : $scriptDir;
        })();

        // We can't easily inject into all render() calls without modifying the engine 
        // or using a specific trait in controllers.
        // But we can add it to the globals if we had a global context manager.
        // Instead, we will rely on controllers passing it, but let's try to make it easy.

        // Actually, Slim 4 doesn't have a built-in "global template data".
        // But we can add it to the request attributes so controllers can pull it.
        $request = $request->withAttribute('base_url', $basePath);

        return $handler->handle($request);
    }
}
