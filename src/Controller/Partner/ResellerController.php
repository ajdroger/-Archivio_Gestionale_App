<?php

namespace MCAG\Controller\Partner;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;

class ResellerController
{
    private $renderer;

    public function __construct(Mustache_Engine $renderer)
    {
        $this->renderer = $renderer;
    }

    public function dashboard(Request $request, Response $response): Response
    {
        // Mock data for Reseller View
        // In real implementation, fetch from DB where partner_id = current_user
        $clients = [
            ['name' => 'Associazione Alpini Milano', 'plan' => 'Pro', 'status' => 'Active', 'revenue' => '€350/mo'],
            ['name' => 'Comune di Vattelapesca', 'plan' => 'Enterprise', 'status' => 'Active', 'revenue' => '€1200/mo'],
            ['name' => 'Clinica San Giorgio', 'plan' => 'Health', 'status' => 'Pending', 'revenue' => '€0/mo'],
        ];

        $stats = [
            'total_clients' => count($clients),
            'monthly_recurring' => '€1,550',
            'commission' => '€465', // 30% share
            'next_payout' => '15 Feb 2026'
        ];

        $html = $this->renderer->render('partner/dashboard', [
            'user' => 'Partner Admin',
            'clients' => $clients,
            'stats' => $stats
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function createClient(Request $request, Response $response): Response
    {
        // Logic to provision a new tenant under this partner
        // ...
        return $response->withHeader('Location', '/partner/dashboard')->withStatus(302);
    }
}
