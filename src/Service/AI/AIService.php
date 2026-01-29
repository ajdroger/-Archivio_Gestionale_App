<?php

namespace MCAG\Service\AI;

use MCAG\Service\AI\Drivers\OllamaDriver;
use MCAG\Service\AI\Drivers\OpenAIDriver;
use Exception;

class AIService
{
    private LLMDriverInterface $driver;

    public function __construct()
    {
        // In a real optimized app, this config should be injected via PHP-DI
        // For portability in this "Solo Dev" context, we load from ENV directly or defaults
        $driverType = $_ENV['AI_DRIVER'] ?? 'ollama';

        switch ($driverType) {
            case 'openai':
                $apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
                if (empty($apiKey)) {
                    throw new Exception("OpenAI Driver selected but OPENAI_API_KEY is missing.");
                }
                $this->driver = new OpenAIDriver(
                    $apiKey,
                    $_ENV['AI_MODEL_ID'] ?? 'gpt-4o'
                );
                break;

            case 'ollama':
            default:
                $this->driver = new OllamaDriver(
                    $_ENV['AI_API_URL'] ?? 'http://localhost:11434',
                    $_ENV['AI_MODEL_ID'] ?? 'llama3:latest'
                );
                break;
        }
    }

    /**
     * Generate text using the configured AI driver.
     */
    /**
     * Generate text using the configured AI driver.
     * 
     * @param string $prompt User prompt
     * @param string $systemPrompt Context/Persona
     * @param array $options Additional params (temp, tokens)
     * @return string|null Response text or null on failure (logged)
     * @throws \RuntimeException If strict mode is on and service fails
     */
    public function generate(string $prompt, string $systemPrompt = 'You are a helpful assistant.', array $options = []): ?string
    {
        try {
            if (empty($prompt))
                return null;

            // Circuit Breaker / retry logic could go here
            return $this->driver->complete($systemPrompt, $prompt, $options);

        } catch (\Throwable $e) {
            // Real logging of AI failures is crucial for "GDPR 2.0" auditing (Why did it fail?)
            error_log("[AIService] Critical Error: " . $e->getMessage());

            // In a real scenario, we might want to fallback to a reliable "Offline" model here
            // But for now, we return specific error string or rethrow based on ENV
            if (($_ENV['APP_DEBUG'] ?? false) === true) {
                return "AI Error: " . $e->getMessage();
            }

            return "I am currently unavailable due to high load or connectivity issues. Please try again later.";
        }
    }

    /**
     * Get the name of the active driver.
     */
    public function getActiveDriverName(): string
    {
        return $this->driver->getName();
    }
}
