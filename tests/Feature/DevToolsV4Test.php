<?php

use FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController;
use FratellanzaMilitare\Controller\DevTools\DevToolsScriptController;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * FEATURE TEST: DevTools V4.0 Upgrade
 * Verifies that the new Terminal and Dashboard components load correctly.
 */

test('v4.0 terminal endpoint executes commands via ScriptController', function () {
    $controller = new DevToolsScriptController();
    $request = (new ServerRequestFactory())->createServerRequest('POST', '/devtools/terminal')
        ->withParsedBody(['cmd' => 'echo "V4.0 Online"']);
    $response = (new ResponseFactory())->createResponse();

    // Mock Session
    session_save_path(sys_get_temp_dir());
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $_SESSION['term_cwd'] = sys_get_temp_dir();

    $result = $controller->terminal($request, $response);
    $body = json_decode((string) $result->getBody(), true);

    expect($result->getStatusCode())->toBe(200);
    expect($body)->toHaveKey('output');
    expect($body['output'])->toContain('V4.0 Online');
});

test('v4.0 dashboard renders with new tabs', function () {
    // Mock Dependencies
    $mustache = $this->createMock(\Mustache_Engine::class);
    $system = $this->createMock(\FratellanzaMilitare\Controller\DevTools\DevToolsSystemController::class);
    $audit = $this->createMock(\FratellanzaMilitare\Controller\DevTools\DevToolsAuditController::class);

    // Setup Mock Returns to avoid null pointers
    $system->method('getSystemInfo')->willReturn(['php_version' => '8.2']);
    $system->method('getHealth')->willReturn(['status' => 'ok']);
    $system->method('getSchemaStats')->willReturn([]);
    $system->method('getRecentLogs')->willReturn([]);
    $system->method('getSessionDebug')->willReturn([]);
    $system->method('scanScripts')->willReturn(['tools' => []]);
    $audit->method('getLogs')->willReturn(['data' => []]);

    // Expect Render Call
    $mustache->expects($this->once())
        ->method('render')
        ->with('devtools', $this->callback(function ($data) {
            // Verify we are passing data for the v4 features
            return isset($data['system']) && isset($data['audit_logs']);
        }))
        ->willReturn('<html>DevTools Dashboard</html>');

    $controller = new DevToolsDashboardController($mustache, $system, $audit);
    $request = (new ServerRequestFactory())->createServerRequest('GET', '/devtools');
    $response = (new ResponseFactory())->createResponse();

    // Mock Admin Session
    $_SESSION['user_role'] = 'admin';
    $_SESSION['username'] = 'AdminTester';

    $result = $controller->dashboard($request, $response);
    expect($result->getStatusCode())->toBe(200);
});
