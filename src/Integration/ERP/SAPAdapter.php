<?php

namespace MCAG\Integration\ERP;

use Exception;
use RuntimeException;

/**
 * SAP Business One Adapter.
 * Connects via Service Layer (OData/REST).
 */
class SAPAdapter implements ERPConnectorInterface
{
    private string $serviceLayerUrl;
    private string $companyDB;
    private string $username;
    private string $password;
    private ?string $sessionId = null;

    public function __construct(string $url, string $companyDB, string $username, string $password)
    {
        $this->serviceLayerUrl = rtrim($url, '/');
        $this->companyDB = $companyDB;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect(): bool
    {
        if (empty($this->serviceLayerUrl))
            return false;

        try {
            // Simulate OData Login Request
            $payload = json_encode([
                'CompanyDB' => $this->companyDB,
                'UserName' => $this->username,
                'Password' => $this->password
            ]);

            // In production:
            // $ch = curl_init($this->serviceLayerUrl . '/Login');
            // ... set headers, post fields ...
            // $result = curl_exec($ch);

            // Mock Success for defined user
            if ($this->username === 'manager') {
                $this->sessionId = "B1SESSION=" . bin2hex(random_bytes(16));
                return true;
            }

            return true; // Allow connection for testing

        } catch (Exception $e) {
            error_log("[SAPAdapter] Connection Failed: " . $e->getMessage());
            return false;
        }
    }

    public function syncEmployees(string $sinceDate): array
    {
        if (!$this->sessionId)
            throw new RuntimeException("SAP Not Connected");

        // OData Query: /b1s/v1/EmployeesInfo?$select=EmployeeID,FirstName,LastName

        return [
            [
                'external_id' => 'SAP-1004',
                'first_name' => 'Hans',
                'last_name' => 'Gruber',
                'role' => 'Director',
                'department' => 'Finance',
                'email' => 'h.gruber@nakatomi.com'
            ]
        ];
    }

    public function pushTimeSheet(array $shiftData): bool
    {
        // OData POST to /b1s/v1/ProjectManagementTimeSheet
        if (!$this->sessionId)
            return false;
        return true;
    }

    public function getProviderName(): string
    {
        return 'SAP Business One (OData)';
    }
}
