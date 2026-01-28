<?php

namespace MCAG\Controller\Partner;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;
use MCAG\Service\ResellerService;

class ResellerController
{
    private $renderer;
    private $service;

    public function __construct(Mustache_Engine $renderer)
    {
        $this->renderer = $renderer;
        // Manual instantiation since DI container update might be complex in this flow
        // Assuming APP_ROOT is accessible or passing relative path
        $this->service = new ResellerService(__DIR__ . '/../../../..');
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $clients = $this->service->getAllClients();
        $stats = $this->service->getAnalytics();

        $csrfNameKey = 'csrf_name';
        $csrfValueKey = 'csrf_value';
        $csrfName = $request->getAttribute($csrfNameKey);
        $csrfValue = $request->getAttribute($csrfValueKey);

        $html = $this->renderer->render('partner/dashboard', [
            'user' => $_SESSION['username'] ?? 'Partner',
            'username' => $_SESSION['username'] ?? 'Partner', // Required by layout_header
            'real_is_admin' => true, // Required to show Admin Menu
            'can_manage_soci' => false, // Partner Dashboard doesn't manage soci
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'P', 0, 1)),
            'clients' => $clients,
            'stats' => $stats,
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            'current_date' => date('d M Y'),
            'page_title' => 'Partner Hub | MCAG',
            'is_partner_mode' => true,
            'csrf' => [
                'keys' => [
                    'name' => $csrfNameKey,
                    'value' => $csrfValueKey
                ],
                'name' => $csrfName,
                'value' => $csrfValue
            ]
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function createClient(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (!empty($data['client_name']) && !empty($data['plan'])) {
            $this->service->createClient([
                'name' => htmlspecialchars($data['client_name']),
                'plan' => htmlspecialchars($data['plan'])
            ]);
        }

        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
    }

    public function handleAction(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $action = $data['action'] ?? '';
        $clientId = $data['client_id'] ?? '';

        if (!$clientId) {
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard?error=missing_id')->withStatus(302);
        }

        switch ($action) {
            case 'suspend':
            case 'activate':
                $this->service->toggleStatus($clientId);
                break;
            case 'delete':
                $this->service->deleteClient($clientId);
                break;
            case 'reset_auth':
                $newPass = $this->service->resetAuth($clientId);
                // In a real scenario we'd email this. Here we flash it to session to show the admin.
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => "Credenziali rigenerate con successo! Nuova password temporanea: <strong>{$newPass}</strong>"
                ];
                break;
        }

        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
    }

    public function accessTenant(Request $request, Response $response, array $args): Response
    {
        $clientId = $args['id'];
        $client = $this->service->getClient($clientId);

        if ($client) {
            // "Real" Login: Set Session Context
            $_SESSION['tenant_id'] = $client['id'];
            $_SESSION['tenant_name'] = $client['name'];

            // Redirect to Main Dashboard with Tenant Context
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/?context=tenant_impersonation')->withStatus(302);
        }

        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard?error=not_found')->withStatus(302);
    }

    public function exitTenant(Request $request, Response $response): Response
    {
        unset($_SESSION['tenant_id']);
        unset($_SESSION['tenant_name']);

        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
    }
}
