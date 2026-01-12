<?php

namespace Tests\Feature\Public;

use Tests\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class DemoRequestTest extends TestCase
{
    public function test_demo_request_validation_fails_empty_data()
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/public/demo-request');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode([]));

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('"success":false', $body);
    }

    public function test_demo_request_validation_fails_invalid_email()
    {
        $data = [
            'nome' => 'Test User',
            'organizzazione' => 'Test Org',
            'email' => 'not-an-email',
            'privacy_consent' => true
        ];

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/public/demo-request');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode($data));

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Email non valida', $body);
    }

    public function test_demo_request_success()
    {
        $data = [
            'nome' => 'Automated Test',
            'organizzazione' => 'PHPUnit Org',
            'ruolo' => 'Tester',
            'email' => 'test@example.com',
            'telefono' => '1234567890',
            'tipo_licenza' => 'saas_cloud',
            'numero_soci' => '100',
            'messaggio' => 'This is a test request.',
            'privacy_consent' => true
        ];

        // Clean up log file before test if possible, or just append
        // We just check the response for now
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/public/demo-request');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode($data));

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('"success":true', $body);

        // Verify file content logic could be here if we really want to verify log writing
        $logFile = __DIR__ . '/../../../../storage/requests/demo_requests.json';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('PHPUnit Org', $content);
    }
}
