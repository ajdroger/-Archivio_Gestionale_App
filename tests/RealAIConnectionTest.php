<?php

namespace MCAG\Tests\Feature;

use PHPUnit\Framework\TestCase;
use MCAG\Service\AI\AIService;
use Dotenv\Dotenv;

/**
 * Real verification of AI Connectivity.
 * WARN: This test makes REAL HTTP requests to external APIs (Ollama/OpenAI) if configured.
 */
class RealAIConnectionTest extends TestCase
{
    private AIService $ai;

    protected function setUp(): void
    {
        // Load real environment
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->safeLoad();

        if (empty($_ENV['AI_DRIVER'])) {
            $this->markTestSkipped('AI Configuration not present in .env');
        }

        $this->ai = new AIService();
    }

    public function test_can_connect_to_provider()
    {
        try {
            $response = $this->ai->generate("Say 'Hello World'", "System Test");

            $this->assertNotEmpty($response, "AI response should not be empty");
            $this->assertStringContainsStringIgnoringCase('Hello', $response);

            fwrite(STDERR, "\n[RealAIConnectionTest] Success! Provider replied: " . substr($response, 0, 50) . "...\n");

        } catch (\Exception $e) {
            $this->fail("Real AI Connection Failed: " . $e->getMessage());
        }
    }
}
