<?php

namespace MCAG\Service\DocumentParser;

interface DocumentParserInterface
{
    /**
     * Extract text from a document.
     */
    public function extractText(string $filePath): string;

    /**
     * Check if this parser supports the given file type.
     */
    public function supports(string $filePath): bool;
}


