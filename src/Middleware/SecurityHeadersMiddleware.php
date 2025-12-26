<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security Headers Middleware
 * Adds comprehensive security headers to all responses
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Content Security Policy
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' fonts.gstatic.com cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com", // Updated for Bootstrap Source Maps
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ]);

        return $response
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }
}
