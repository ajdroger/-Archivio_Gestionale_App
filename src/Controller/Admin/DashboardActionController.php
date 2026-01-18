<?php

namespace MCAG\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MCAG\Service\ConfigurationService; // Hypothetical service, will mock if needed
use Psr\Log\LoggerInterface;
use Monolog\Logger;

/**
 * Controller per le azioni interattive della Dashboard "Mission Control".
 * Gestisce switch operativi, broadcast e note rapide.
 */
class DashboardActionController
{
    private LoggerInterface $logger;
    private ConfigurationService $config;

    public function __construct(LoggerInterface $logger, ConfigurationService $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Gestisce i toggle della "Switchboard" (Manutenzione, Iscrizioni, ecc.)
     * POST /admin/dashboard/toggle
     */
    public function toggleConfig(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $setting = $data['setting'] ?? null;
        // Handle boolean value correctly from JSON or Form
        $value = $data['value'] ?? false;

        // Sanitize boolean
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        if (!$setting) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Missing setting']));
            return $response->withStatus(400);
        }

        // Persist Configuration
        $this->config->set($setting, $value);

        $this->logger->info("Dashboard Toggle: $setting set to " . ($value ? 'ON' : 'OFF'));

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

        // Persist Notes
        $this->config->set('admin_notes', $notes);

        $response->getBody()->write(json_encode(['success' => true, 'saved_at' => date('H:i:s')]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
