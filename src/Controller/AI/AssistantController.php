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

    public function __construct(
        $view,
        OllamaProvider $llm,
        SimpleVectorStore $vectorStore,
        LoggerInterface $logger
    ) {
        $this->view = $view;
        $this->llm = $llm;
        $this->vectorStore = $vectorStore;
        $this->logger = $logger;
    }

    /**
     * Renders the minimal chat interface (HTMX partial).
     */
    public function chatWindow(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/assistant.mustache', [
            'is_available' => $this->llm->isAvailable()
        ]);
    }

    /**
     * Handles user message, retrieves context, and generates response.
     */
    public function message(Request $request, Response $response): Response
    {
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
        $systemPrompt = "Sei 'Archivio Parlante', l'assistente AI della Fratellanza Militare. ";
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
}
