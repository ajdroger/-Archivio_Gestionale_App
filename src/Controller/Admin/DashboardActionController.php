<?php

namespace MCAG\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MCAG\Service\ConfigurationService; // Hypothetical service, will mock if needed
use Monolog\Logger;

/**
 * Controller per le azioni interattive della Dashboard "Mission Control".
 * Gestisce switch operativi, broadcast e note rapide.
 */
class DashboardActionController
{
    private Logger $logger;

    // In a real app, I'd inject a ConfigService. 
    // For now, I'll simulate config persistence via JSON file or specific service if available.

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Gestisce i toggle della "Switchboard" (Manutenzione, Iscrizioni, ecc.)
     * POST /admin/dashboard/toggle
     */
    public function toggleConfig(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $setting = $data['setting'] ?? null;
        $value = $data['value'] ?? false; // true/false

        if (!$setting) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Missing setting']));
            return $response->withStatus(400);
        }

        // Simula salvataggio configurazione
        $this->logger->info("Dashboard Toggle: $setting set to " . ($value ? 'ON' : 'OFF'));

        // Qui si chiamerebbe $this->configService->set($setting, $value);
        // Per ora ritorniamo successo simulato.

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => "Configurazione '$setting' aggiornata con successo.",
            'new_state' => $value
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Invia un Broadcast (simulato)
     * POST /admin/dashboard/broadcast
     */
    public function sendBroadcast(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $target = $data['target'] ?? 'all';
        $message = $data['message'] ?? '';

        if (empty($message)) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Empty message']));
            return $response->withStatus(400);
        }

        $this->logger->info("Broadcast Sent to [$target]: $message");

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => "Messaggio inviato a $target destinatari."
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Salva note (Sticky Notes)
     * POST /admin/dashboard/notes
     */
    public function saveNotes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $notes = $data['notes'] ?? '';

        // Simula persistenza
        // file_put_contents('notes.txt', $notes);

        $response->getBody()->write(json_encode(['success' => true, 'saved_at' => date('H:i:s')]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
