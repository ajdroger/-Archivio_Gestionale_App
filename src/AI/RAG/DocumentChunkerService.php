<?php

namespace FratellanzaMilitare\AI\RAG;

/**
 * Class DocumentChunkerService
 * 
 * Splits large texts into smaller chunks to fit into LLM context windows
 * and improve retrieval accuracy.
 * 
 * @package FratellanzaMilitare\AI\RAG
 */
class DocumentChunkerService
{
    /**
     * Split text into chunks by max characters.
     * Tries to split on sentence boundaries (. ! ?) to preserve meaning.
     */
    public function split(string $text, int $maxChars = 500): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk) + strlen($sentence) > $maxChars) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }
            }
            $currentChunk .= $sentence . ' ';
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
