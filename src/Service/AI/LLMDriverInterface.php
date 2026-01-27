<?php

namespace MCAG\Service\AI;

interface LLMDriverInterface
{
    /**
     * Send a completion request to the LLM.
     *
     * @param string $systemPrompt The system instruction (persona/context).
     * @param string $userPrompt The user's query or content to process.
     * @param array $options Optional parameter overrides (temp, max_tokens, etc).
     * @return string|null The generated text or null on failure.
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): ?string;

    /**
     * Get the name of the driver (e.g., 'ollama', 'openai').
     */
    public function getName(): string;
}
