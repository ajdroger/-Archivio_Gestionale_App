<?php

use FratellanzaMilitare\Controller\Auth\LoginFlowController;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

test('login form renders', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(\Mustache_Engine::class);
    $mustache->expects($this->once())
        ->method('render')
        ->willReturn('<form>MCAG Login</form>');

    $validator = new \FratellanzaMilitare\Service\InputValidator();
    $controller = new LoginFlowController($mustache, $validator);

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/login');
    $response = (new ResponseFactory())->createResponse();

    $result = $controller->form($request, $response);

    expect($result->getStatusCode())->toBe(200);
});

test('login verify success redirects', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(\Mustache_Engine::class);

    $validator = new \FratellanzaMilitare\Service\InputValidator();
    $controller = new LoginFlowController($mustache, $validator);

    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/login')
        ->withParsedBody(['username' => 'admin', 'password' => 'admin123']));

    $response = (new ResponseFactory())->createResponse();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $result = $controller->verifyCredentials($request, $response);

    expect($result->getStatusCode())->toBe(302);
    expect($result->getHeaderLine('Location'))->toContain('/login/2fa');
    expect($_SESSION['partial_auth'])->toBeTrue();
});
