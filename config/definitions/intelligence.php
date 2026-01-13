<?php

use Psr\Container\ContainerInterface;
use FratellanzaMilitare\Service\DocumentParser\PdfParserService;
use FratellanzaMilitare\Service\DocumentParser\WordParserService;
use FratellanzaMilitare\Service\DocumentParser\ExcelParserService;
use FratellanzaMilitare\Service\DocumentParser\CodeParserService;
use FratellanzaMilitare\Service\DocumentParser\DocumentParserFactory;

return [
    \FratellanzaMilitare\Controller\Intelligence\StatsDashboardController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Intelligence\StatsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class),
            $c->get(\FratellanzaMilitare\Debug\ResilienceMonitor::class),
            $c->get(\FratellanzaMilitare\Service\HealthCheckService::class)
        );
    },
    \FratellanzaMilitare\Controller\Intelligence\ReportExportController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Intelligence\ReportExportController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },

    // AI & RAG Engine Definitions
    \FratellanzaMilitare\AI\Providers\OllamaProvider::class => function (ContainerInterface $c) {
        // Base URL da .env o default localhost
        $host = $_ENV['OLLAMA_HOST'] ?? 'http://127.0.0.1:11434';
        return new \FratellanzaMilitare\AI\Providers\OllamaProvider(
            $c->get(\Psr\Log\LoggerInterface::class),
            $host
        );
    },

    \FratellanzaMilitare\AI\RAG\SimpleVectorStore::class => function (ContainerInterface $c) {
        $storagePath = __DIR__ . '/../../storage/ai/vector_store.json';
        return new \FratellanzaMilitare\AI\RAG\SimpleVectorStore(
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

    \FratellanzaMilitare\AI\RAG\DocumentChunkerService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\AI\RAG\DocumentChunkerService();
    },

    \FratellanzaMilitare\Service\AI\DocumentIngestionService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\AI\DocumentIngestionService(
            $c->get(DocumentParserFactory::class),
            $c->get(\FratellanzaMilitare\AI\RAG\DocumentChunkerService::class),
            $c->get(\FratellanzaMilitare\AI\RAG\SimpleVectorStore::class),
            $c->get(\FratellanzaMilitare\AI\Providers\OllamaProvider::class)
        );
    },

    \FratellanzaMilitare\Controller\AI\AssistantController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\AI\AssistantController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\AI\Providers\OllamaProvider::class),
            $c->get(\FratellanzaMilitare\AI\RAG\SimpleVectorStore::class),
            $c->get(\Psr\Log\LoggerInterface::class),
            $c->get(\FratellanzaMilitare\Queue\QueueInterface::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },
];
