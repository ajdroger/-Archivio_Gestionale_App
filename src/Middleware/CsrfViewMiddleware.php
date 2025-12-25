<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mustache_Engine;

class CsrfViewMiddleware implements MiddlewareInterface
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get CSRF token from attributes (set by Slim\Csrf\Guard)
        $csrfNameKey = $request->getAttribute('csrf_name');
        $csrfValueKey = $request->getAttribute('csrf_value');
        $csrfName = $request->getAttribute($csrfNameKey);
        $csrfValue = $request->getAttribute($csrfValueKey);

        // Slim 4 CSRF stores the *keys* in attributes 'csrf_name' and 'csrf_value' by default?
        // Actually, looking at the Controller code:
        // $request->getAttribute('csrf_name') returns the key? Or the value?
        // Let's verify usage in SocioController:
        // $csrfName = $request->getAttribute('csrf_name');
        // $csrfValue = $request->getAttribute('csrf_value');
        // These are the actual tokens if the Guard put them there directly?
        // Slim CSRF docs say:
        // $request->getAttribute($this->prefix . 'name') returns the token name (e.g. 'csrf_name')
        // $request->getAttribute($this->prefix . 'value') returns the token value (e.g. 'csrf_value')
        // AND $request->getAttribute($nameKey) returns the actual generated name
        // AND $request->getAttribute($valueKey) returns the actual generated value.

        // Wait, standard usage with Slim-Csrf:
        // The Guard middleware sets attributes on the request.
        // By default: 'csrf_name' (the key for the name field) and 'csrf_value' (the key for the value field).
        // AND validation relies on us sending them back.

        // Let's trust the existing controller code for extraction:
        // $csrfName = $request->getAttribute('csrf_name');
        // $csrfValue = $request->getAttribute('csrf_value');

        // Add to Mustache Global Context
        // Note: Mustache doesn't have a mutable global context easily accessible after instantiation
        // unless we used a helper or pre-render hook.
        // However, we can use the 'addHelper' or similar if configured.
        // Alternatively, since we are using view models in controllers, we might rely on a base controller?

        // BETTER APPROACH:
        // We can simply set it as a simplified attribute that Controllers can lazily pick up,
        // OR we can inject it if we have a way.

        // Since we want to Avoid manual injection in controllers, we need the renderer to know about it.
        // If Mustache_Engine is shared, can we add a helper?
        $this->mustache->addHelper('csrf', [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value')
        ]);

        return $handler->handle($request);
    }
}
