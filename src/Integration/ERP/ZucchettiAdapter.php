<?php

namespace MCAG\Integration\ERP;

class ZucchettiAdapter implements ERPConnectorInterface
{
    private string $endpoint;
    private string $apiKey;
    private $connected = false;

    public function __construct(string $endpoint, string $apiKey)
    {
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;
    }

    public function connect(): bool
    {
        // Simulation of SOAP/REST Auth to Zucchetti HR Suite
        // In a real scenario: $client = new SoapClient($this->endpoint . '?wsdl');
        if (!empty($this->apiKey)) {
            $this->connected = true;
            return true;
        }
        return false;
    }

    public function syncEmployees(string $sinceDate): array
    {
        if (!$this->connected) {
            throw new \Exception("Zucchetti ERP not connected.");
        }

        // Mock response from Zucchetti API
        return [
            [
                'external_id' => 'ZUC-001',
                'first_name' => 'Mario',
                'last_name' => 'Bianchi',
                'role' => 'Magazziniere',
                'department' => 'Logistica A',
                'email' => 'mario.bianchi@aziendax.it'
            ],
            [
                'external_id' => 'ZUC-009',
                'first_name' => 'Anna',
                'last_name' => 'Verdi',
                'role' => 'Amministrativo',
                'department' => 'HR',
                'email' => 'anna.verdi@aziendax.it'
            ]
        ];
    }

    public function pushTimeSheet(array $shiftData): bool
    {
        if (!$this->connected)
            return false;

        // Logic to push XML payload to Zucchetti Presenze Project
        // error_log("Pushing shift " . $shiftData['id'] . " to Zucchetti...");
        return true;
    }

    public function getProviderName(): string
    {
        return 'Zucchetti HR Suite';
    }
}
