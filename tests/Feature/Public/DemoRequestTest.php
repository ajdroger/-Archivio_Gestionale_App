<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

class DemoRequestTest extends TestCase
{
    public function test_demo_request_validation_fails_empty_data()
    {
        $response = $this->post('/api/public/demo-request', []);
        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    public function test_demo_request_validation_fails_invalid_email()
    {
        $data = [
            'nome' => 'Test User',
            'organizzazione' => 'Test Org',
            'email' => 'not-an-email',
            'privacy_consent' => true
        ];
        $response = $this->post('/api/public/demo-request', $data);
        $response->assertStatus(400);
        $response->assertSee('Email non valida');
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
        $response = $this->post('/api/public/demo-request', $data);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify file content logic could be here if we really want to verify log writing
        $logFile = __DIR__ . '/../../../../storage/requests/demo_requests.json';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('PHPUnit Org', $content);
    }
}
