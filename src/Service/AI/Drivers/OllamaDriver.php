<?php

namespace MCAG\Service\AI\Drivers;

use MCAG\Service\AI\LLMDriverInterface;

class OllamaDriver implements LLMDriverInterface
{
    private string $baseUrl;
    private string $model;

    public function __construct(string $baseUrl = 'http://localhost:11434', string $model = 'llama3:latest')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): ?string
    {
        $endpoint = $this->baseUrl . '/v1/chat/completions';

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
        ];

        return $this->makeRequest($endpoint, $payload);
    }

    public function getName(): string
    {
        return 'ollama';
    }

    private function makeRequest(string $url, array $payload): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            // Log error here in a real app
            return null;
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
}
