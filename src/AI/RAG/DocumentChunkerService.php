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
    public function split(string $text, int $maxChars = 800): array
    {
        $chunks = [];

        // Split by Markdown headers (Level 1, 2, 3) to preserve section context
        // This regex finds lines starting with #, ##, ###
        $sections = preg_split('/^(?="#{1,3}\s)/m', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section))
                continue;

            // If section is small enough, keep it whole
            if (strlen($section) <= $maxChars) {
                $chunks[] = $section;
            } else {
                // If section is huge, fall back to sentence splitting
                $sentences = preg_split('/(?<=[.?!])\s+/', $section, -1, PREG_SPLIT_NO_EMPTY);
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
            }
        }

        return $chunks;
    }
}
