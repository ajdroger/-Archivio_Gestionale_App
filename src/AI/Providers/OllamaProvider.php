<?php

namespace FratellanzaMilitare\AI\Providers;

use FratellanzaMilitare\AI\LLMProviderInterface;
use FratellanzaMilitare\AI\EmbeddingProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Class OllamaProvider
 * 
 * Implementation for local Ollama instance (typically running on port 11434).
 * Supports both Generation (Llama3/Mistral) and Embeddings (nomic-embed-text).
 * 
 * @package FratellanzaMilitare\AI\Providers
 */
class OllamaProvider implements LLMProviderInterface, EmbeddingProviderInterface
{
    private string $baseUrl;
    private string $modelChat;
    private string $modelEmbed;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        string $baseUrl = 'http://localhost:11434',
        string $modelChat = 'llama3',
        string $modelEmbed = 'nomic-embed-text'
    ) {
        $this->logger = $logger;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->modelChat = $modelChat;
        $this->modelEmbed = $modelEmbed;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(string $prompt, array $context = []): string
    {
        if (!$this->isAvailable()) {
            return "⚠️ Ollama non è raggiungibile. Risposta simulata: 'Ho ricevuto la tua richiesta: $prompt'";
        }

        $payload = [
            'model' => $this->modelChat,
            'prompt' => $prompt,
            'stream' => false,
            'context' => $context
        ];

        try {
            $response = $this->request('/api/generate', $payload);
            return $response['response'] ?? 'Errore nella generazione della risposta.';
        } catch (\Throwable $e) {
            $this->logger->error("Ollama Generate Error: " . $e->getMessage());
            return "Errore AI: " . $e->getMessage();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function embed(string $text): array
    {
        // Mock fallback if unavailable (to prevent crashes during dev)
        if (!$this->isAvailable()) {
            $this->logger->warning("Ollama unavailable for embedding. Returning zero vector.");
            return array_fill(0, 10, 0.1); // Mock vector
        }

        $payload = [
            'model' => $this->modelEmbed,
            'prompt' => $text
        ];

        try {
            $response = $this->request('/api/embeddings', $payload);
            return $response['embedding'] ?? [];
        } catch (\Throwable $e) {
            $this->logger->error("Ollama Embedding Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Head request only
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Fast timeout
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Helper for HTTP requests
     */
    private function request(string $endpoint, array $data): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \RuntimeException('Curl Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("Ollama API Error ($httpCode): $result");
        }

        return json_decode($result, true) ?? [];
    }
}
