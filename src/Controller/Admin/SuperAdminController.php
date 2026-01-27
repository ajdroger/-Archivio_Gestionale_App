<?php

namespace MCAG\Controller\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use PDO;

class SuperAdminController
{
    private $view;
    private $adminMiddleware;

    public function __construct($view)
    {
        $this->view = $view;
    }

    /**
     * Dashboard Principale Super Admin
     * Mostra lista tenant e statistiche globali.
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $pdo = DatabaseConnection::getConnection();

        // Fetch Tenants
        // Note: Assumes 'tenants' table exists in the Core DB. 
        // In a real bootstrap, we'd ensure migration runs first.
        try {
            $stmt = $pdo->query("SELECT * FROM tenants ORDER BY created_at DESC");
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $tenants = [];
            $error = "Core DB 'tenants' table not found. Please run migration.";
        }

        // Calculate Global Stats
        $activeTenants = count(array_filter($tenants, fn($t) => $t['status'] === 'active'));
        $totalRevenue = 0; // Placeholder

        return $this->view->render($response, 'admin/tenants_dashboard.mustache', [
            'tenants' => $tenants,
            'stats' => [
                'total' => count($tenants),
                'active' => $activeTenants,
                'suspended' => count($tenants) - $activeTenants,
                'revenue' => $totalRevenue
            ],
            'error' => $error ?? null,
            'page_title' => 'SaaS Super Admin'
        ]);
    }

    /**
     * Crea un nuovo Tenant (Database + Entry)
     */
    public function createTenant(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $name = $data['name'];
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['subdomain']));
        $plan = $data['plan'];

        if (empty($name) || empty($subdomain)) {
            return $this->view->render($response, 'admin/tenants_dashboard.mustache', ['error' => 'Missing fields']);
        }

        $pdo = DatabaseConnection::getConnection();
        $dbName = "mcag_tenant_" . $subdomain;

        try {
            // 1. Register in Core DB
            $stmt = $pdo->prepare("INSERT INTO tenants (id, name, subdomain, db_name, status, plan_id, created_at) VALUES (UUID(), :name, :sub, :db, 'active', :plan, NOW())");
            $stmt->execute([
                'name' => $name,
                'sub' => $subdomain,
                'db' => $dbName,
                'plan' => $plan
            ]);

            // 2. Create Database (Provisioning)
            // WARNING: User must have CREATE DATABASE privs
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 3. Clone Schema (Simplified for MVP)
            // In prod: Run Phinx migration on new DB
            // $this->runMigrations($dbName);

            return $response->withHeader('Location', '/super-admin')->withStatus(302);

        } catch (\Exception $e) {
            return $this->view->render($response, 'admin/tenants_dashboard.mustache', ['error' => 'Provisioning Failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sospende un Tenant
     */
    public function toggleStatus(Request $request, Response $response, $args): Response
    {
        $id = $args['id'];
        $pdo = DatabaseConnection::getConnection();

        // Toggle Active/Suspended
        $pdo->prepare("UPDATE tenants SET status = IF(status='active','suspended','active') WHERE id = ?")->execute([$id]);

        return $response->withHeader('Location', '/super-admin')->withStatus(302);
    }
}
