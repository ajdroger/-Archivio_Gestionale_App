<?php

namespace FratellanzaMilitare\Service\DocumentParser;

use Smalot\PdfParser\Parser;

class PdfParserService implements DocumentParserInterface
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        if (!$this->supports($filePath)) {
            throw new \RuntimeException("Unsupported file type (expected PDF)");
        }

        $pdf = $this->parser->parseFile($filePath);
        return $pdf->getText();
    }

    public function supports(string $filePath): bool
    {
        $mimeType = mime_content_type($filePath);
        return $mimeType === 'application/pdf';
    }
}
