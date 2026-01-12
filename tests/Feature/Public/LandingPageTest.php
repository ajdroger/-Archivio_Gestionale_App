<?php

namespace Tests\Feature\Public;

use Tests\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class LandingPageTest extends TestCase
{
    public function test_landing_page_loads_correctly()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertStringContainsString('Gestione Archivi', $body);
        $this->assertStringContainsString('Mission Critical', $body);
        $this->assertStringContainsString('Inizia Demo Gratuita', $body);
    }

    public function test_navbar_contains_demo_request_button()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('Richiedi Accesso', $body);
        $this->assertStringContainsString('data-bs-target="#demoModal"', $body);
    }

    public function test_benchmark_pdf_link_exists()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('MCAG_Benchmark_2026.pdf', $body);
    }

    public function test_legal_links_are_present()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('legal/PRIVACY_POLICY.md', $body);
        $this->assertStringContainsString('legal/EULA.md', $body);
    }
}
