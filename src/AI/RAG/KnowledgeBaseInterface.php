<?php

namespace MCAG\AI\RAG;

/**
 * Interface KnowledgeBaseInterface
 * 
 * Defines the contract for storing and retrieving document chunks
 * and their vector embeddings.
 * 
 * @package MCAG\AI\RAG
 */
interface KnowledgeBaseInterface
{
    /**
     * Add a document (and its embedding) to the knowledge base.
     * 
     * @param string $id Unique identifier for the document.
     * @param string $content Human-readable content.
     * @param array $embedding Vector embedding of the content.
     * @param array $metadata Additional metadata (e.g., source, author).
     * @return void
     */
    public function addDocument(string $id, string $content, array $embedding, array $metadata = []): void;

    /**
     * Search for relevant documents similar to the query vector.
     * 
     * @param array $queryEmbedding The embedding of the search query.
     * @param int $limit Max number of results.
     * @return array Array of matching documents (['content' => ..., 'score' => ...]).
     */
    public function search(array $queryEmbedding, int $limit = 3): array;
}


