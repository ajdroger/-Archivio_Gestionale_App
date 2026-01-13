<?php

namespace MCAG\AI;

/**
 * Interface LLMProviderInterface
 * 
 * Defines the contract for interacting with Large Language Models.
 * Agnostic to the underlying provider (Ollama, OpenAI, Anthropic).
 * 
 * @package MCAG\AI
 */
interface LLMProviderInterface
{
    /**
     * Send a prompt to the LLM and get a text response.
     * 
     * @param string $prompt The user query or system prompt.
     * @param array $context Optional conversation history or context.
     * @return string The generated text.
     */
    public function generate(string $prompt, array $context = []): string;

    /**
     * Check if the provider is available/healthy.
     * 
     * @return bool
     */
    public function isAvailable(): bool;
}


