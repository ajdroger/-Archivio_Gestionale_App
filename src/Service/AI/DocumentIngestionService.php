<?php

namespace MCAG\Service\AI;

use MCAG\Service\DocumentParser\DocumentParserFactory;
use MCAG\AI\RAG\DocumentChunkerService;
use MCAG\AI\RAG\SimpleVectorStore;
use MCAG\AI\Providers\OllamaProvider;

class DocumentIngestionService
{
    private DocumentParserFactory $parserFactory;
    private DocumentChunkerService $chunker;
    private SimpleVectorStore $vectorStore;
    private OllamaProvider $llm;

    public function __construct(
        DocumentParserFactory $parserFactory,
        DocumentChunkerService $chunker,
        SimpleVectorStore $vectorStore,
        OllamaProvider $llm
    ) {
        $this->parserFactory = $parserFactory;
        $this->chunker = $chunker;
        $this->vectorStore = $vectorStore;
        $this->llm = $llm;
    }

    /**
     * Ingest a document: parse, chunk, embed, and store.
     * Supports: PDF, DOCX, XLSX
     * 
     * @param string $filePath Path to the file
     * @return int Number of chunks created
     */
    public function ingest(string $filePath): int
    {
        // 1. Select appropriate parser and extract text
        $parser = $this->parserFactory->getParser($filePath);
        $text = $parser->extractText($filePath);

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
                    'chunk_index' => $index,
                    'file_type' => pathinfo($filePath, PATHINFO_EXTENSION)
                ]);
                $chunksCreated++;
            }
        }

        return $chunksCreated;
    }
}


