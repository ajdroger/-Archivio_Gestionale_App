<?php

namespace FratellanzaMilitare\Controller\AI;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\AI\Providers\OllamaProvider;
use FratellanzaMilitare\AI\RAG\SimpleVectorStore;
use Psr\Log\LoggerInterface;

class AssistantController
{
    private $view;
    private $llm;
    private $vectorStore;
    private $logger;
    private $queue;

    public function __construct(
        $view,
        OllamaProvider $llm,
        SimpleVectorStore $vectorStore,
        LoggerInterface $logger,
        \FratellanzaMilitare\Queue\QueueInterface $queue
    ) {
        $this->view = $view;
        $this->llm = $llm;
        $this->vectorStore = $vectorStore;
        $this->logger = $logger;
        $this->queue = $queue;
    }

    /**
     * Renders the minimal chat interface (HTMX partial).
     */
    public function chatWindow(Request $request, Response $response): Response
    {
        try {
            $this->logger->info("AssistantController: Loading chat window.");
            $available = false;
            try {
                $available = $this->llm->isAvailable();
                $this->logger->info("AssistantController: Ollama availability checked: " . ($available ? 'YES' : 'NO'));
            } catch (\Throwable $e) {
                $this->logger->error("AssistantController: Ollama check failed: " . $e->getMessage());
                $available = false;
            }

            return $this->view->render($response, 'admin/assistant.mustache', [
                'is_available' => $available
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("AssistantController: Critical error: " . $e->getMessage());
            $response->getBody()->write("CRITICAL AI ERROR: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
            return $response->withStatus(500);
        }
    }

    /**
     * Handles user message, retrieves context, and generates response.
     */
    public function message(Request $request, Response $response): Response
    {
        // Increase timeout for AI generation
        set_time_limit(120);

        $data = $request->getParsedBody();
        $userMessage = trim($data['message'] ?? '');

        if (empty($userMessage)) {
            return $response;
        }

        // 1. Embed Query
        $logger = $this->logger;
        $embedding = $this->llm->embed($userMessage);

        // 2. Retrieve Context (RAG)
        $context = "";
        if (!empty($embedding)) {
            $results = $this->vectorStore->search($embedding, 3);
            foreach ($results as $res) {
                // Only use high relevance
                if ($res['score'] > 0.6) {
                    $context .= "- " . $res['content'] . "\n";
                }
            }
        }

        // 3. Prompt Engineering
        $systemPrompt = "Sei 'Archivio Parlante', l'assistente AI avanzato del sistema MCAG v5.0 (Militare Civile Archivio Gestionale). ";
        $systemPrompt .= "Il progetto è sviluppato da Soobadur Mohammad Ajmeer come soluzione commerciale ad alte prestazioni ('Singularity Edition'). ";
        $systemPrompt .= "Non sei affiliato alla 'Fratellanza Militare' se non come software di gestione. ";
        $systemPrompt .= "Rispondi in italiano in modo formale e preciso. ";
        $systemPrompt .= "Usa SOLO le informazioni fornite nel contesto seguente per rispondere. ";
        $systemPrompt .= "Se non sai la risposta, dillo chiaramente.\n\n";
        $systemPrompt .= "CONTESTO:\n$context\n\n";
        $systemPrompt .= "DOMANDA: $userMessage";

        // 4. Generate Answer
        $answer = $this->llm->generate($systemPrompt);

        // 5. Render Message Bubble (HTMX Swap)
        $html = '
        <div class="d-flex justify-content-end mb-2">
            <div class="bg-primary text-white p-2 rounded-3" style="max-width: 80%">' . htmlspecialchars($userMessage) . '</div>
        </div>
        <div class="d-flex justify-content-start mb-2">
            <div class="bg-dark text-light p-2 rounded-3 border border-secondary" style="max-width: 80%">
                <i class="fa-solid fa-robot text-warning me-2"></i> ' . nl2br(htmlspecialchars($answer)) . '
            </div>
        </div>';

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Handles document upload for RAG ingestion (Async via Queue).
     */
    public function uploadDocument(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['document'])) {
            $response->getBody()->write('<div class="alert alert-danger">Nessun file selezionato.</div>');
            return $response;
        }

        /** @var \Psr\Http\Message\UploadedFileInterface $uploadedFile */
        $uploadedFile = $uploadedFiles['document'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write('<div class="alert alert-danger">Errore upload: ' . $uploadedFile->getError() . '</div>');
            return $response;
        }

        $filename = $uploadedFile->getClientFilename();
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $filename);
        $targetPath = __DIR__ . '/../../../storage/knowledge_base/' . $filename;

        // Ensure dir exists
        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0755, true);
        }

        $uploadedFile->moveTo($targetPath);

        // Push to Queue (Async)
        try {
            $job = new \FratellanzaMilitare\Queue\Job\DocumentIngestionJob($targetPath, $filename);
            $this->queue->push($job);

            $html = '<div class="d-flex justify-content-start mb-2">
                    <div class="bg-dark text-light p-2 rounded-3 border border-secondary" style="max-width: 80%">
                        <i class="fa-solid fa-file-import text-success me-2"></i> Documento <strong>' . htmlspecialchars($filename) . '</strong> in coda di elaborazione (Background).
                    </div>
                </div>';
        } catch (\Throwable $e) {
            $this->logger->error("Queue Push Failed: " . $e->getMessage());
            $html = '<div class="alert alert-danger">Errore accodamento: ' . $e->getMessage() . '</div>';
        }

        $response->getBody()->write($html);
        return $response;
    }
}
