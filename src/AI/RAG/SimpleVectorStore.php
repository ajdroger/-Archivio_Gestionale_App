<?php

namespace MCAG\AI\RAG;

use Psr\Log\LoggerInterface;

/**
 * Class SimpleVectorStore
 * 
 * A simple, file-based vector store for the MVP.
 * Stores documents and embeddings in a JSON file.
 * Performs linear search with cosine similarity (slow for millions, fine for thousands).
 * 
 * @package MCAG\AI\RAG
 */
class SimpleVectorStore implements KnowledgeBaseInterface
{
    private string $storagePath;
    private array $data = []; // ['id' => ['content' => ..., 'embedding' => ..., 'metadata' => ...]]
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $storagePath)
    {
        $this->logger = $logger;
        $this->storagePath = $storagePath;
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->storagePath)) {
            $content = file_get_contents($this->storagePath);
            $this->data = json_decode($content, true) ?? [];
        } else {
            // Create directory if not exists
            $dir = dirname($this->storagePath);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);
        }
    }

    private function save(): void
    {
        file_put_contents($this->storagePath, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * {@inheritDoc}
     */
    public function addDocument(string $id, string $content, array $embedding, array $metadata = []): void
    {
        $this->data[$id] = [
            'id' => $id,
            'content' => $content,
            'embedding' => $embedding,
            'metadata' => $metadata,
            'timestamp' => time()
        ];
        $this->save();
        $this->logger->info("AI: Document '$id' added to Vector Store.");
    }

    /**
     * {@inheritDoc}
     */
    public function search(array $queryEmbedding, int $limit = 3): array
    {
        $results = [];

        foreach ($this->data as $id => $doc) {
            if (empty($doc['embedding']))
                continue;

            $similarity = $this->cosineSimilarity($queryEmbedding, $doc['embedding']);
            $results[] = [
                'id' => $id,
                'content' => $doc['content'],
                'metadata' => $doc['metadata'],
                'score' => $similarity
            ];
        }

        // Sort by score (descending)
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = min(count($vecA), count($vecB));

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0)
            return 0.0;

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}


