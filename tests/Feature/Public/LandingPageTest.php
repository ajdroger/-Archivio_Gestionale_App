<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    private string $htmlContent;

    protected function setUp(): void
    {
        parent::setUp();
        // Load the static HTML file directly since it's not served by Slim
        $path = __DIR__ . '/../../../public/landing/index.html';
        if (!file_exists($path)) {
            $this->fail("Landing page file not found at: $path");
        }
        $this->htmlContent = file_get_contents($path);
    }

    public function test_landing_page_loads_correctly()
    {
        $this->assertStringContainsString('Gestione Archivi', $this->htmlContent);
        $this->assertStringContainsString('Mission Critical', $this->htmlContent);
        $this->assertStringContainsString('Inizia Demo Gratuita', $this->htmlContent);
    }

    public function test_navbar_contains_demo_request_button()
    {
        $this->assertStringContainsString('Richiedi Accesso', $this->htmlContent);
        $this->assertStringContainsString('data-bs-target="#demoModal"', $this->htmlContent);
    }

    public function test_benchmark_pdf_link_exists()
    {
        $this->assertStringContainsString('MCAG_Benchmark_2026.pdf', $this->htmlContent);
    }

    public function test_legal_links_are_present()
    {
        $this->assertStringContainsString('legal/PRIVACY_POLICY.md', $this->htmlContent);
        $this->assertStringContainsString('legal/EULA.md', $this->htmlContent);
    }
}
