<?php

namespace MCAG\Service\DocumentParser;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;

class WordParserService implements DocumentParserInterface
{
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        if (!$this->supports($filePath)) {
            throw new \RuntimeException("Unsupported file type (expected Word Document)");
        }

        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                $text .= $this->extractTextFromContainer($section) . "\n";
            }

            return trim($text);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error parsing Word document: " . $e->getMessage(), 0, $e);
        }
    }

    private function extractTextFromContainer(AbstractContainer $container): string
    {
        $text = '';
        foreach ($container->getElements() as $element) {
            if ($element instanceof Text) {
                $text .= $element->getText() . "\n";
            } elseif ($element instanceof TextRun) {
                foreach ($element->getElements() as $textElement) {
                    if ($textElement instanceof Text) {
                        $text .= $textElement->getText();
                    }
                }
                $text .= "\n";
            }
            // Add other elements if needed (Tables, etc.)
        }
        return $text;
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, ['docx', 'doc', 'rtf']);
    }
}


