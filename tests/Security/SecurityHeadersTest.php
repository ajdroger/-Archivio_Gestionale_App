<?php

use MCAG\Middleware\SecurityHeadersMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\ServerRequestFactory;

describe('SecurityHeadersMiddleware Tests', function () {

    test('adds Content-Security-Policy header', function () {
        $middleware = new SecurityHeadersMiddleware();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $response = $middleware->process($request, $handler);

        expect($response->hasHeader('Content-Security-Policy'))->toBeTrue();
        $csp = $response->getHeaderLine('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain("frame-ancestors 'none'");
    })->group('security', 'critical');

    test('adds X-Frame-Options DENY header', function () {
        $middleware = new SecurityHeadersMiddleware();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $response = $middleware->process($request, $handler);

        expect($response->hasHeader('X-Frame-Options'))->toBeTrue();
        expect($response->getHeaderLine('X-Frame-Options'))->toBe('DENY');
    })->group('security', 'critical');

    test('all security headers are present', function () {
        $middleware = new SecurityHeadersMiddleware();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $response = $middleware->process($request, $handler);

        $requiredHeaders = [
            'Content-Security-Policy',
            'X-Frame-Options',
            'X-Content-Type-Options',
            'X-XSS-Protection',
            'Referrer-Policy',
            'Permissions-Policy'
        ];

        foreach ($requiredHeaders as $header) {
            expect($response->hasHeader($header))->toBeTrue();
        }
    })->group('security', 'critical');
});
