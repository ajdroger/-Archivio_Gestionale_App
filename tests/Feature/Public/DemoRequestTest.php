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

        if ($response->getStatusCode() === 302) {
            $this->fail("Redirected to: " . $response->getHeaderLine('Location'));
        }

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

        // Mock Email Service (Should NOT be called here, but good practice to safegaurd)
        $mockEmail = $this->createMock(\FratellanzaMilitare\Service\EmailServiceInterface::class);
        $this->app->getContainer()->set(\FratellanzaMilitare\Service\EmailServiceInterface::class, $mockEmail);

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

        // MOCK Email Service to prevent mail() calls and verify interaction
        $mockEmail = $this->createMock(\FratellanzaMilitare\Service\EmailServiceInterface::class);
        $mockEmail->expects($this->once())
            ->method('send')
            ->willReturn(true);

        // Override container definition
        $this->app->getContainer()->set(\FratellanzaMilitare\Service\EmailServiceInterface::class, $mockEmail);


        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/public/demo-request');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode($data));

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('"success":true', $body);
    }
}
