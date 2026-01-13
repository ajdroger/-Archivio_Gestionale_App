<?php

namespace FratellanzaMilitare\Controller\Public;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class DemoRequestController
{
    private string $storagePath;
    private $emailService;

    public function __construct(\FratellanzaMilitare\Service\EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
        // Ensure storage directory exists
        $this->storagePath = __DIR__ . '/../../../../storage/requests';
        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    #[OA\Post(
        path: '/api/public/demo-request',
        summary: 'Invia una richiesta di demo/accesso',
        tags: ['Public'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nome', type: 'string'),
                    new OA\Property(property: 'organizzazione', type: 'string'),
                    new OA\Property(property: 'ruolo', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'telefono', type: 'string'),
                    new OA\Property(property: 'tipo_licenza', type: 'string'),
                    new OA\Property(property: 'numero_soci', type: 'string'),
                    new OA\Property(property: 'messaggio', type: 'string'),
                    new OA\Property(property: 'privacy_consent', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Richiesta inviata con successo'),
            new OA\Response(response: 400, description: 'Dati non validi')
        ]
    )]
    public function submit(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Basic Validation
        if (empty($data['nome']) || empty($data['email']) || empty($data['organizzazione']) || empty($data['privacy_consent'])) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Campi obbligatori mancanti.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email non valida.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Secure Headers
        $entry = [
            'id' => uniqid('demo_'),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'data' => [
                'nome' => strip_tags($data['nome']),
                'organizzazione' => strip_tags($data['organizzazione']),
                'ruolo' => strip_tags($data['ruolo'] ?? ''),
                'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                'telefono' => strip_tags($data['telefono'] ?? ''),
                'tipo_licenza' => strip_tags($data['tipo_licenza'] ?? ''),
                'numero_soci' => strip_tags($data['numero_soci'] ?? ''),
                'messaggio' => strip_tags($data['messaggio'] ?? ''),
            ]
        ];

        // Save to JSON Log (Rotated by date could be better, but single file for simplicity now)
        $logFile = $this->storagePath . '/demo_requests.json';

        // Append to array in file (not efficient for huge scale, but perfect for landing page requests)
        $currentData = [];
        if (file_exists($logFile)) {
            $json = file_get_contents($logFile);
            $currentData = json_decode($json, true) ?? [];
        }
        $currentData[] = $entry;

        if (file_put_contents($logFile, json_encode($currentData, JSON_PRETTY_PRINT))) {

            // Send Email Notification
            $to = 'ajmeer03@gmail.com'; // Admin Email
            $subject = 'Nuova Richiesta Demo/Preventivo - MCAG Landing';
            $message = "Hai ricevuto una nuova richiesta dal sito:\n\n";
            $message .= "Nome: " . $entry['data']['nome'] . "\n";
            $message .= "Organizzazione: " . $entry['data']['organizzazione'] . "\n";
            $message .= "Ruolo: " . $entry['data']['ruolo'] . "\n";
            $message .= "Email: " . $entry['data']['email'] . "\n";
            $message .= "Telefono: " . $entry['data']['telefono'] . "\n";
            $message .= "Tipo Licenza: " . $entry['data']['tipo_licenza'] . "\n";
            $message .= "Soci Stimati: " . $entry['data']['numero_soci'] . "\n";
            $message .= "Messaggio:\n" . $entry['data']['messaggio'] . "\n\n";
            $message .= "Data: " . $entry['timestamp'] . "\n";
            $message .= "IP: " . $entry['ip'];

            // Use injected EmailService instead of mail()
            try {
                $this->emailService->send($to, $subject, $message, [], ['Reply-To' => $entry['data']['email']]);
            } catch (\Exception $e) {
                // Log failure but return success to user as the request was saved
                // In production logger log: $this->logger->error("Mail failed: " . $e->getMessage());
            }

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Richiesta ricevuta. La contatteremo a breve.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Errore interno nel salvataggio.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
