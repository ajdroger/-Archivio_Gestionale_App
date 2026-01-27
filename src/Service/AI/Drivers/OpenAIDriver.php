<?php

namespace MCAG\Service\AI\Drivers;

use MCAG\Service\AI\LLMDriverInterface;

class OpenAIDriver implements LLMDriverInterface
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gpt-4o')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): ?string
    {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

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
        return 'openai';
    }

    private function makeRequest(string $url, array $payload): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
}
