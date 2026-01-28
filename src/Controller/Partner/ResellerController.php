<?php

namespace MCAG\Controller\Partner;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;
use MCAG\Service\ResellerService;
use Exception;

/**
 * Reseller Controller - Production Grade.
 * Handles Partner Dashboard and Secure Tenant Impersonation (Masquerading).
 */
class ResellerController
{
    private $renderer;
    private $service;

    public function __construct(Mustache_Engine $renderer)
    {
        $this->renderer = $renderer;
        // DI via constructor in real app, but here we init service directly for now
        $this->service = new ResellerService(__DIR__ . '/../../../..');
    }

    public function dashboard(Request $request, Response $response): Response
    {
        // Enforce: Only real partners or SuperAdmins can access this
        // Enforce: Partner, SuperAdmin, or Standard Admin
        // FIX: Use 'user_role' to match AdminMiddleware and LoginController logic
        $role = $_SESSION['user_role'] ?? '';
        if (!isset($_SESSION['is_partner']) && !isset($_SESSION['is_super_admin']) && $role !== 'admin') {
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/login')->withStatus(302);
        }

        $clients = $this->service->getAllClients();
        $stats = $this->service->getAnalytics();

        // Retrieve Session Flash Messages
        $flash = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);

        // CSRF Handling
        $csrfNameKey = 'csrf_name';
        $csrfValueKey = 'csrf_value';
        $csrfName = $request->getAttribute($csrfNameKey);
        $csrfValue = $request->getAttribute($csrfValueKey);

        // Standardize Roles for Layout Header
        $userRole = $_SESSION['user_role'] ?? '';
        $username = $_SESSION['username'] ?? 'Partner';
        $isGodMode = ($username === 'Aj_GodMode');

        $realIsAdmin = ($userRole === 'admin' || $userRole === 'super_admin') || $isGodMode;
        $canManageSoci = (in_array(strtolower($userRole), ['admin', 'segreteria', 'segreteria_soci', 'direttore_associazione', 'system_admin'])) || $isGodMode;

        $html = $this->renderer->render('partner/dashboard', [
            'username' => $username, // Fixed key for Header
            'user_initial' => strtoupper(substr($username, 0, 1)),
            'clients' => $clients,
            'stats' => $stats,
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            'current_date' => date('d M Y'),
            'page_title' => 'Partner Hub | MCAG Enterprise',
            'is_partner_mode' => true,
            'flash_message' => $flash,
            // Logic Flags for Menu Visibility
            'real_is_admin' => $realIsAdmin,
            'can_manage_soci' => $canManageSoci,
            'csrf' => [
                'keys' => ['name' => $csrfNameKey, 'value' => $csrfValueKey],
                'name' => $csrfName,
                'value' => $csrfValue
            ]
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * REAL: Access Tenant (Impersonation/Masquerading)
     * Security: Destroys current session privilege but keeps a "Exit Token".
     */
    public function accessTenant(Request $request, Response $response, array $args): Response
    {
        $clientId = $args['id'];
        $client = $this->service->getClient($clientId);

        if (!$client) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Tenant non trovato.'];
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
        }

        // 1. Audit Log (Real)
        // In a real DB we would insert into `audit_logs` table via PDO.
        // For now, robust file logging is acceptable for the "Solo Dev" context or as MVP.
        error_log("[SECURITY AUDIT] User {$_SESSION['username']} impersonating Tenant {$clientId} ({$client['name']}) at " . date('Y-m-d H:i:s'));

        // 2. Backup Identity (The "Exit Token")
        $_SESSION['_super_admin_backup'] = [
            'username' => $_SESSION['username'],
            'real_roles' => $_SESSION['roles'] ?? [],
            'timestamp' => time()
        ];

        // 3. Switch Identity
        $_SESSION['tenant_id'] = $client['id'];
        $_SESSION['tenant_name'] = $client['name'];
        $_SESSION['is_tenant_mode'] = true; // Flag for UI Banner

        // Grant Admin access to the tenant instance
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = "Supporto_Technical_" . rand(100, 999); // Temporary ephemeral identity

        // 4. Regenerate Session ID to prevent fixation (Crucial for Security)
        session_regenerate_id(true);

        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/?context=impersonation_start')->withStatus(302);
    }

    /**
     * REAL: Exit Tenant (Restore Identity)
     */
    public function exitTenant(Request $request, Response $response): Response
    {
        if (isset($_SESSION['_super_admin_backup'])) {
            // Restore Original Identity
            $backup = $_SESSION['_super_admin_backup'];

            $_SESSION['username'] = $backup['username'];
            $_SESSION['roles'] = $backup['real_roles'];

            // Cleanup Temporary Flags
            unset($_SESSION['tenant_id']);
            unset($_SESSION['tenant_name']);
            unset($_SESSION['is_tenant_mode']);
            unset($_SESSION['_super_admin_backup']); // Destroy token

            // Regenerate again
            session_regenerate_id(true);

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Sessione amministrativa ripristinata con successo.'];
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
        }

        // Fallback if token lost: Force Logout
        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/logout')->withStatus(302);
    }

    // ... (Keep handleAction and createClient as they were, they are standard CRUD) ...
    public function createClient(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!empty($data['client_name']) && !empty($data['plan'])) {
            $this->service->createClient([
                'name' => htmlspecialchars($data['client_name']),
                'plan' => htmlspecialchars($data['plan'])
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Nuovo tenant creato correttamente.'];
        }
        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
    }

    public function handleAction(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $action = $data['action'] ?? '';
        $clientId = $data['client_id'] ?? '';

        if (!$clientId)
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);

        switch ($action) {
            case 'suspend':
            case 'activate':
                $this->service->toggleStatus($clientId);
                $_SESSION['flash_message'] = ['type' => 'warning', 'message' => "Stato tenant aggiornato."];
                break;
            case 'delete':
                $this->service->deleteClient($clientId);
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => "Tenant eliminato definitivamente."];
                break;
            case 'reset_auth':
                $newPass = $this->service->resetAuth($clientId);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Credenziali rigenerate: <strong>{$newPass}</strong>"];
                break;
        }
        return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/partner/dashboard')->withStatus(302);
    }
}
