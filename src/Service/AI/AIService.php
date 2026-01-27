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
    public function generate(string $prompt, string $systemPrompt = 'You are a helpful assistant.', array $options = []): ?string
    {
        return $this->driver->complete($systemPrompt, $prompt, $options);
    }

    /**
     * Get the name of the active driver.
     */
    public function getActiveDriverName(): string
    {
        return $this->driver->getName();
    }
}
