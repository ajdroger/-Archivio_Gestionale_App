<?php

namespace MCAG\Controller\API;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MCAG\Service\AI\AIService;
use MCAG\Service\Audit\AIAuditLogger;

class AIChatController
{
    private AIService $aiService;
    private AIAuditLogger $auditLogger;

    public function __construct(AIService $aiService, AIAuditLogger $auditLogger)
    {
        $this->aiService = $aiService;
        $this->auditLogger = $auditLogger;
    }

    public function chat(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userMessage = $data['message'] ?? '';
        $context = $data['context'] ?? 'general_assistant';

        if (empty($userMessage)) {
            $response->getBody()->write(json_encode(['error' => 'Message is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $start = microtime(true);

        // System Prompt based on context
        $systemPrompt = "You are 'MCAG Genius', a helpful AI assistant for the MCAG Enterprise System. 
                         You are polite, concise, and technical. You help users navigate the software.";

        if ($context === 'code_helper') {
            $systemPrompt .= " You specialize in explaining PHP code and SQL queries.";
        }

        // Call AI Service
        $aiResponse = $this->aiService->generate($userMessage, $systemPrompt);

        // Calculate Latency
        $latencyMs = (microtime(true) - $start) * 1000;

        // Log for GDPR
        $this->auditLogger->logInteraction(
            $this->aiService->getActiveDriverName(),
            $context,
            $userMessage,
            $aiResponse ?? 'ERROR',
            $latencyMs
        );

        if ($aiResponse === null) {
            $response->getBody()->write(json_encode(['error' => 'AI Service Unavailable']));
            return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'response' => $aiResponse,
            'driver' => $this->aiService->getActiveDriverName(),
            'latency_ms' => round($latencyMs, 2)
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
