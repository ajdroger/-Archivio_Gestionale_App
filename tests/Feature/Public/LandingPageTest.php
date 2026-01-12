<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_loads_correctly()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Gestione Archivi');
        $response->assertSee('Mission Critical');
        $response->assertSee('Richiedi Accesso');
        $response->assertSee('Inizia Demo Gratuita');
    }

    public function test_navbar_contains_demo_request_button()
    {
        $response = $this->get('/');
        $response->assertSee('Richiedi Accesso');
        $response->assertSee('data-bs-target="#demoModal"', false);
    }

    public function test_benchmark_pdf_link_exists()
    {
        $response = $this->get('/');
        $response->assertSee('MCAG_Benchmark_2026.pdf');
    }

    public function test_legal_links_are_present()
    {
        $response = $this->get('/');
        $response->assertSee('legal/PRIVACY_POLICY.md');
        $response->assertSee('legal/EULA.md');
    }
}
