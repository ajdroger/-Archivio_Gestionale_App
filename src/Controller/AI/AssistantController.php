<?php

namespace MCAG\Controller\AI;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MCAG\AI\Providers\OllamaProvider;
use MCAG\AI\RAG\SimpleVectorStore;
use Psr\Log\LoggerInterface;

class AssistantController
{
    private $view;
    private $llm;
    private $vectorStore;
    private $logger;
    private $queue;
    private $socioRepo;

    public function __construct(
        $view,
        OllamaProvider $llm,
        SimpleVectorStore $vectorStore,
        LoggerInterface $logger,
        \MCAG\Queue\QueueInterface $queue,
        \MCAG\GestioneSoci\SocioRepository $socioRepo
    ) {
        $this->view = $view;
        $this->llm = $llm;
        $this->vectorStore = $vectorStore;
        $this->logger = $logger;
        $this->queue = $queue;
        $this->socioRepo = $socioRepo;
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
            } catch (\Throwable $e) {
                $available = false;
            }

            $content = $this->view->render('admin/assistant.mustache', [
                'is_available' => $available,
                'csrf' => [
                    'name' => $request->getAttribute('csrf_name'),
                    'value' => $request->getAttribute('csrf_value'),
                    'keys' => ['name' => 'csrf_name', 'value' => 'csrf_value']
                ],
                'base_url' => (function () {
                    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                    return $scriptDir === '/' ? '' : $scriptDir;
                })()
            ]);

            $response->getBody()->write($content);
            return $response;
        } catch (\Throwable $e) {
            $this->logger->error("AssistantController: Critical error: " . $e->getMessage());
            $response->getBody()->write("Error loading AI Interface.");
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
        $contextUrl = $data['context_url'] ?? '';
        $contextTitle = $data['context_title'] ?? '';

        if (empty($userMessage)) {
            return $response;
        }

        // 1. Embed Query
        $embedding = $this->llm->embed($userMessage);

        // 2. Retrieve Document Context (RAG)
        $ragContext = "";
        if (!empty($embedding)) {
            // RELAXED RAG SETTINGS (v5.2.1)
            // Increased limit to 10 to capture more widespread context
            // Lowered threshold to 0.45 to ensure code blocks and specific technical details are retrieved
            $results = $this->vectorStore->search($embedding, 10);
            foreach ($results as $res) {
                if ($res['score'] > 0.45) {
                    $ragContext .= "- [Source: {$res['metadata']['source_file']}] " . $res['content'] . "\n\n";
                }
            }
        }

        // 3. Smart Context Injection (User Navigation)
        $smartContext = "";
        if (!empty($contextUrl)) {
            // Detect Socio Detail Page (/soci/detail/{cf} or similar)
            // Pattern: .../soci/ABC123456...
            if (preg_match('/\/soci\/([A-Z0-9]{16})/i', $contextUrl, $matches)) {
                $cf = $matches[1];
                try {
                    $socio = $this->socioRepo->findByCodiceFiscale($cf);
                    if ($socio) {
                        $smartContext .= "\n[CONTESTO UTENTE]: L'utente sta visualizzando la scheda del socio:\n";
                        $smartContext .= "Nome: {$socio->getNome()} {$socio->getCognome()}\n";
                        $smartContext .= "CF: {$socio->getCodiceFiscale()}\n";
                        $smartContext .= "Email: {$socio->getEmail()}\n";
                        $smartContext .= "Stato: {$socio->getStato()}\n";
                        $smartContext .= "Moroso: " . ($socio->isMoroso() ? 'SÌ' : 'NO') . "\n";
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning("Smart Context failed lookup: " . $e->getMessage());
                }
            } elseif (strpos($contextUrl, '/statistiche') !== false) {
                try {
                    $stats = $this->socioRepo->getStatistics();
                    $smartContext .= "\n[CONTESTO UTENTE]: L'utente sta guardando la Dashboard Statistiche.\n";
                    $smartContext .= "Totale Soci: {$stats['total']}\n";
                    $smartContext .= "Attivi: {$stats['active']}\n";
                } catch (\Throwable $e) {
                }
            }
        }

        // 4. Prompt Engineering
        $systemPrompt = "Sei 'Archivio Parlante', assistente AI del sistema MCAG v5.2 'Omni-Reader'. ";
        $systemPrompt .= "Hai accesso a documenti interni (Decision Log, Changelog, Benchmark).\n";
        $systemPrompt .= "IMPORTANTE: Rispondi SEMPRE in ITALIANO. Usa le INFORMAZIONI CONTESTUALI fornite qui sotto per rispondere. Se trovi la risposta nel contesto, usala. Non inventare.\n\n";

        // RBAC: Check User Role for Context Sanitization
        $userRole = $_SESSION['user_role'] ?? 'guest';
        $isGodMode = ($userRole === 'Aj_GodMode' || $userRole === 'admin');

        if (!empty($smartContext)) {
            // If NOT GodMode/Admin, sanitize technical details if present (basic security)
            if (!$isGodMode) {
                // Example: Hide internal IDs or sensitive debugging info if it were in the context
                $smartContext = preg_replace('/ID Interno: \d+/', 'ID Interno: [RISERVATO]', $smartContext);
            }
            $systemPrompt .= "### CONTESTO UTENTE (Pagina Corrente):\n$smartContext\n\n";
        }

        if (!empty($ragContext)) {
            // RBAC for RAG: Some documents might be restricted (future proofing)
            // For now, we trust the VectorStore retrieval, but we can filter here if needed.
            $systemPrompt .= "### DOCUMENTAZIONE INTERNA (Knowledge Base):\n$ragContext\n\n";
        }

        if ($isGodMode) {
            $systemPrompt .= "[SISTEMA]: L'utente è un SUPER ADMIN/DEV (Aj_GodMode). Puoi fornire dettagli tecnici, stack trace, path di file e configurazioni.\n";
        } else {
            $systemPrompt .= "[SISTEMA]: L'utente è un operatore standard. NON fornire dettagli tecnici bassi (no stack trace, no percorsi file, no config server). Rispondi in modo funzionale.\n";
        }

        $systemPrompt .= "DOMANDA UTENTE: $userMessage";

        // 5. Generate Answer
        $answer = $this->llm->generate($systemPrompt);

        // 6. Render Message Bubble
        $html = '
        <div class="d-flex justify-content-end mb-2">
            <div class="bg-primary text-white p-2 rounded-3 small" style="max-width: 85%">' . htmlspecialchars($userMessage) . '</div>
        </div>
        <div class="d-flex justify-content-start mb-2">
            <div class="bg-dark text-light p-2 rounded-3 border border-secondary small" style="max-width: 85%">
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
            $job = new \MCAG\Queue\Job\DocumentIngestionJob($targetPath, $filename);
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


