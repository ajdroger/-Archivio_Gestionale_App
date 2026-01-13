<?php

namespace FratellanzaMilitare\Service\DocumentParser;

class CodeParserService implements DocumentParserInterface
{
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        if (!$this->supports($filePath)) {
            throw new \RuntimeException("Unsupported file type");
        }

        $content = file_get_contents($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Wrap in markdown code block for better LLM understanding
        return "```{$extension}\n" . $content . "\n```";
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $supported = [
            'txt',
            'md',
            'markdown',         // Text
            'php',
            'js',
            'html',
            'css',      // Web
            'py',
            'java',
            'c',
            'cpp',
            'cs',  // Backend/System
            'json',
            'yaml',
            'yml',
            'xml',    // Data
            'sql',
            'sh',
            'bat'               // Scripts
        ];

        return in_array($extension, $supported);
    }
}
