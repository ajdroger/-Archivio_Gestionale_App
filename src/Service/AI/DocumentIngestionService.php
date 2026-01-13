<?php

namespace FratellanzaMilitare\Service\AI;

use FratellanzaMilitare\Service\DocumentParser\PdfParserService;
use FratellanzaMilitare\AI\RAG\DocumentChunkerService;
use FratellanzaMilitare\AI\RAG\SimpleVectorStore;
use FratellanzaMilitare\AI\Providers\OllamaProvider;

class DocumentIngestionService
{
    private PdfParserService $pdfParser;
    private DocumentChunkerService $chunker;
    private SimpleVectorStore $vectorStore;
    private OllamaProvider $llm;

    public function __construct(
        PdfParserService $pdfParser,
        DocumentChunkerService $chunker,
        SimpleVectorStore $vectorStore,
        OllamaProvider $llm
    ) {
        $this->pdfParser = $pdfParser;
        $this->chunker = $chunker;
        $this->vectorStore = $vectorStore;
        $this->llm = $llm;
    }

    /**
     * Ingest a PDF document: parse, chunk, embed, and store.
     * 
     * @param string $filePath Path to the PDF file
     * @return int Number of chunks created
     */
    public function ingest(string $filePath): int
    {
        // 1. Extract text from PDF
        $text = $this->pdfParser->extractText($filePath);

        // 2. Split into chunks
        $chunks = $this->chunker->split($text);

        // 3. Generate embeddings and store
        $chunksCreated = 0;
        foreach ($chunks as $index => $chunk) {
            $embedding = $this->llm->embed($chunk);
            if (!empty($embedding)) {
                // Generate unique ID for each chunk
                $chunkId = md5($filePath . '_' . $index);
                $this->vectorStore->addDocument($chunkId, $chunk, $embedding, [
                    'source_file' => basename($filePath),
                    'chunk_index' => $index
                ]);
                $chunksCreated++;
            }
        }

        return $chunksCreated;
    }
}
