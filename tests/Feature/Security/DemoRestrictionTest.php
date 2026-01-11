<?php

use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use FratellanzaMilitare\Controller\SettingsController;
use FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController;

/**
 * SECURITY TEST: Demo Mode Restrictions
 * Verifies that sensitive areas are blocked when in Demo Mode.
 */

test('settings page returns 403 in demo mode', function () {
    // 1. Setup Environment
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $_SESSION['is_demo_mode'] = true;
    $_SESSION['user_role'] = 'demo';

    // 2. Mock Dependencies
    $mustache = $this->createMock(\Mustache_Engine::class);
    $mustache->method('render')->willReturn('<h1>Access Denied</h1>');

    $controller = new SettingsController($mustache);

    // 3. Request
    $request = (new ServerRequestFactory())->createServerRequest('GET', '/impostazioni');
    $response = (new ResponseFactory())->createResponse();

    // 4. Execution
    $result = $controller->view($request, $response);

    // 5. Assertion
    expect($result->getStatusCode())->toBe(403);
});

test('devtools dashboard returns 403 in demo mode', function () {
    // 1. Setup Environment
    $_SESSION['is_demo_mode'] = true;

    // 2. Mock Dependencies
    $mustache = $this->createMock(\Mustache_Engine::class);
    $mustache->method('render')->willReturn('<h1>Access Denied</h1>');

    $system = $this->createMock(\FratellanzaMilitare\Controller\DevTools\DevToolsSystemController::class);
    $audit = $this->createMock(\FratellanzaMilitare\Controller\DevTools\DevToolsAuditController::class);
    $demo = $this->createMock(\FratellanzaMilitare\Service\Demo\DemoInvitationService::class);

    $controller = new DevToolsDashboardController($mustache, $system, $audit, $demo);

    // 3. Request
    $request = (new ServerRequestFactory())->createServerRequest('GET', '/devtools');
    $response = (new ResponseFactory())->createResponse();

    // 4. Execution
    $result = $controller->dashboard($request, $response);

    // 5. Assertion
    expect($result->getStatusCode())->toBe(403);
});
