<?php

namespace MCAG\AI;

/**
 * Interface EmbeddingProviderInterface
 * 
 * Defines the contract for generating vector embeddings from text.
 * Used for RAG (Retrieval-Augmented Generation).
 * 
 * @package MCAG\AI
 */
interface EmbeddingProviderInterface
{
    /**
     * Generate an embedding vector for the given text.
     * 
     * @param string $text
     * @return array<float> The vector representation.
     */
    public function embed(string $text): array;
}


