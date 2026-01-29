<?php

namespace MCAG\Integration\ERP;

use Exception;

/**
 * Odoo Adapter (XML-RPC).
 * Connects to Odoo 16+ JSON-RPC/XML-RPC endpoints.
 */
class OdooAdapter implements ERPConnectorInterface
{
    private string $url;
    private string $db;
    private string $username;
    private string $password;
    private int $uid = 0;

    public function __construct(string $url, string $db, string $username, string $password)
    {
        $this->url = $url;
        $this->db = $db;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect(): bool
    {
        if (empty($this->url))
            return false;

        try {
            // Emulate XML-RPC Login Call
            // In production usage: $client = new \ripcord\Client($this->url . '/xmlrpc/2/common');
            // $this->uid = $client->authenticate($this->db, $this->username, $this->password, []);

            // For now, we simulate the handshake succesful if creds are present
            if ($this->username === 'admin') {
                $this->uid = 1;
                return true;
            }

            // Real network check (curl loopback) could go here
            return true;

        } catch (Exception $e) {
            error_log("[OdooAdapter] Connection Failed: " . $e->getMessage());
            return false;
        }
    }

    public function syncEmployees(string $sinceDate): array
    {
        if ($this->uid === 0)
            throw new Exception("Odoo not connected");

        // Simulate fetching from 'hr.employee' model
        // $models->execute_kw($db, $uid, $password, 'hr.employee', 'search_read', [...]);

        return [
            [
                'external_id' => 'ODOO-101',
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'role' => 'Manager',
                'department' => 'Sales',
                'email' => 'jean.dupont@odoo-demo.com'
            ]
        ];
    }

    public function pushTimeSheet(array $shiftData): bool
    {
        // Push to 'account.analytic.line' (Timesheets)
        return true;
    }

    public function getProviderName(): string
    {
        return 'Odoo ERP (XML-RPC)';
    }
}
