<?php

use FratellanzaMilitare\Controller\LoginController;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

test('login form renders', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(Mustache_Engine::class);
    $mustache->expects($this->once())
        ->method('render')
        ->willReturn('<form>Login</form>');

    $controller = new LoginController($mustache);

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/login')
        ->withAttribute('csrf_name', 'csrf_name')
        ->withAttribute('csrf_value', 'csrf_token');

    $response = (new ResponseFactory())->createResponse();

    $result = $controller->form($request, $response);

    expect($result->getStatusCode())->toBe(200);
});

test('login verify success redirects', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(Mustache_Engine::class);

    $controller = new LoginController($mustache);

    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/login')
        ->withParsedBody(['username' => 'admin', 'password' => 'password']));

    $response = (new ResponseFactory())->createResponse();

    // Start Session for testing
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $result = $controller->verifyCredentials($request, $response);

    expect($result->getStatusCode())->toBe(302);
    expect($result->getHeaderLine('Location'))->toContain('/login/2fa');
    expect($_SESSION['partial_auth'])->toBeTrue();
});
