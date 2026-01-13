<?php

namespace MCAG\Service\DocumentParser;

use Psr\Container\ContainerInterface;

class DocumentParserFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getParser(string $filePath): DocumentParserInterface
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'pdf':
                return $this->container->get(PdfParserService::class);
            case 'docx':
            case 'doc':
            case 'rtf':
                return $this->container->get(WordParserService::class);
            case 'xlsx':
            case 'xls':
            case 'csv':
            case 'ods':
                return $this->container->get(ExcelParserService::class);
            case 'txt':
            case 'md':
            case 'php':
            case 'js':
            case 'html':
            case 'css':
            case 'py':
            case 'java':
            case 'c':
            case 'cpp':
            case 'sql':
            case 'json':
            case 'yaml':
            case 'xml':
            case 'sh':
            case 'bat':
                return $this->container->get(CodeParserService::class);
            default:
                throw new \RuntimeException("Unsupported file extension: $extension");
        }
    }
}


