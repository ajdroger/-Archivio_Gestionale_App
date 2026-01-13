<?php

namespace MCAG\Event\Listeners;

use MCAG\Event\ListenerInterface;
use MCAG\Event\Events\SocioCreatedEvent;
use MCAG\AI\EmbeddingProviderInterface;
use MCAG\AI\RAG\KnowledgeBaseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class IndexSocioListener
 * 
 * Listens for new Socio creation and indexes their data into the AI Knowledge Base.
 * This makes the socio immediately searchable by the RAG system.
 * 
 * @package MCAG\Event\Listeners
 */
class IndexSocioListener implements ListenerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EmbeddingProviderInterface $embeddingProvider,
        private KnowledgeBaseInterface $vectorStore
    ) {
    }

    public function handle(object $event): void
    {
        if (!$event instanceof SocioCreatedEvent) {
            return;
        }

        try {
            // 1. Prepare Content for AI
            // We create a natural language representation of the entity
            $content = "Socio: {$event->nome} {$event->cognome}. ";
            $content .= "Codice Fiscale: {$event->codiceFiscale}. ";
            $content .= "ID Sistema: {$event->socioId}. ";
            $content .= "Creato da: {$event->createdBy} il " . $event->getOccurredOn()->format('d/m/Y') . ".";

            // 2. Generate Embedding
            // If the provider fails (or is mock), it returns a zero vector (handled by provider)
            $vector = $this->embeddingProvider->embed($content);

            if (empty($vector)) {
                $this->logger->warning("AI Indexing skipped for Socio {$event->socioId}: No embedding generated.");
                return;
            }

            // 3. Store in Knowledge Base
            $docId = "socio_" . $event->socioId;
            $this->vectorStore->addDocument($docId, $content, $vector, [
                'type' => 'socio',
                'entity_id' => $event->socioId,
                'created_at' => time()
            ]);

            $this->logger->info("AI: Indexed Socio {$event->socioId} successfully.");

        } catch (\Throwable $e) {
            $this->logger->error("AI Indexing Failed for Socio {$event->socioId}: " . $e->getMessage());
        }
    }
}


