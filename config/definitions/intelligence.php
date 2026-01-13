<?php

use Psr\Container\ContainerInterface;
use MCAG\Service\DocumentParser\PdfParserService;
use MCAG\Service\DocumentParser\WordParserService;
use MCAG\Service\DocumentParser\ExcelParserService;
use MCAG\Service\DocumentParser\CodeParserService;
use MCAG\Service\DocumentParser\DocumentParserFactory;

return [
    \MCAG\Controller\Intelligence\StatsDashboardController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Intelligence\StatsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\GestioneSoci\SocioRepository::class),
            $c->get(\MCAG\Debug\ResilienceMonitor::class),
            $c->get(\MCAG\Service\HealthCheckService::class)
        );
    },
    \MCAG\Controller\Intelligence\ReportExportController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Intelligence\ReportExportController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\GestioneSoci\SocioRepository::class)
        );
    },

    // AI & RAG Engine Definitions
    \MCAG\AI\Providers\OllamaProvider::class => function (ContainerInterface $c) {
        // Base URL da .env o default localhost
        $host = $_ENV['OLLAMA_HOST'] ?? 'http://127.0.0.1:11434';
        return new \MCAG\AI\Providers\OllamaProvider(
            $c->get(\Psr\Log\LoggerInterface::class),
            $host
        );
    },

    \MCAG\AI\RAG\SimpleVectorStore::class => function (ContainerInterface $c) {
        $storagePath = __DIR__ . '/../../storage/ai/vector_store.json';
        return new \MCAG\AI\RAG\SimpleVectorStore(
            $c->get(\Psr\Log\LoggerInterface::class),
            $storagePath
        );
    },

    PdfParserService::class => function (ContainerInterface $c) {
        return new PdfParserService();
    },

    WordParserService::class => function (ContainerInterface $c) {
        return new WordParserService();
    },

    ExcelParserService::class => function (ContainerInterface $c) {
        return new ExcelParserService();
    },

    CodeParserService::class => function (ContainerInterface $c) {
        return new CodeParserService();
    },

    DocumentParserFactory::class => function (ContainerInterface $c) {
        return new DocumentParserFactory($c);
    },

    \MCAG\AI\RAG\DocumentChunkerService::class => function (ContainerInterface $c) {
        return new \MCAG\AI\RAG\DocumentChunkerService();
    },

    \MCAG\Service\AI\DocumentIngestionService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\AI\DocumentIngestionService(
            $c->get(DocumentParserFactory::class),
            $c->get(\MCAG\AI\RAG\DocumentChunkerService::class),
            $c->get(\MCAG\AI\RAG\SimpleVectorStore::class),
            $c->get(\MCAG\AI\Providers\OllamaProvider::class)
        );
    },

    \MCAG\Controller\AI\AssistantController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\AI\AssistantController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\AI\Providers\OllamaProvider::class),
            $c->get(\MCAG\AI\RAG\SimpleVectorStore::class),
            $c->get(\Psr\Log\LoggerInterface::class),
            $c->get(\MCAG\Queue\QueueInterface::class),
            $c->get(\MCAG\GestioneSoci\SocioRepository::class)
        );
    },
];


