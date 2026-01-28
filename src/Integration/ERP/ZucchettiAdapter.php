<?php

namespace MCAG\Integration\ERP;

use Exception;
use SoapClient;
use SoapFault;
use RuntimeException;

/**
 * Zucchetti HR Suite Production Adapter.
 * 
 * Provides real connectivity to Zucchetti HR Enterprise endpoints via SOAP/REST.
 * Handles Authentication, Token Management and Error Reporting.
 * 
 * @package MCAG\Integration\ERP
 */
class ZucchettiAdapter implements ERPConnectorInterface
{
    private string $endpoint;
    private string $apiKey;
    private string $username;

    /** @var SoapClient|null */
    private $soapClient;

    /** @var string|null Session token from Zucchetti */
    private ?string $authToken = null;

    private bool $connected = false;

    // Connection timeout in seconds
    private const TIMEOUT = 10;

    public function __construct(string $endpoint, string $apiKey, string $username = '')
    {
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;
        $this->username = $username;
    }

    /**
     * Establishes a real connection to the Zucchetti SOAP Endpoint.
     * 
     * @return bool
     * @throws RuntimeException If connection fails with a critical error.
     */
    public function connect(): bool
    {
        if (empty($this->endpoint)) {
            error_log("[ZucchettiAdapter] Endpoint not configured.");
            return false;
        }

        try {
            // Real interaction: Initialize SoapClient with WSDL
            // We use 'exceptions' => true to catch SoapFaults
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false, // Often needed for internal enterprise CAs
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ],
                'http' => [
                    'timeout' => self::TIMEOUT
                ]
            ]);

            $this->soapClient = new SoapClient($this->endpoint . '?wsdl', [
                'stream_context' => $context,
                'exceptions' => true,
                'trace' => 1, // Enable tracing for debug if needed
                'cache_wsdl' => WSDL_CACHE_DISK
            ]);

            // Attempt Login / Handshake
            $response = $this->soapClient->Login([
                'user' => $this->username,
                'password' => $this->apiKey, // Assuming API Key acts as password or token
                'env' => 'PROD'
            ]);

            if (isset($response->LoginResult) && $response->LoginResult->success) {
                $this->authToken = $response->LoginResult->token;
                $this->connected = true;
                return true;
            }

            error_log("[ZucchettiAdapter] Auth Failed: " . ($response->LoginResult->message ?? 'Unknown Error'));
            return false;

        } catch (SoapFault $e) {
            // Clean handling of SOAP errors (Service Unavailable, 404, etc)
            error_log("[ZucchettiAdapter] SOAP Fault: " . $e->getMessage());
            // We do NOT throw here to allow the app to degrade gracefully, 
            // but the method returns false so the caller knows.
            return false;
        } catch (Exception $e) {
            error_log("[ZucchettiAdapter] Critical Connection Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronizes employee registry from ERP.
     * 
     * @param string $sinceDate format YYYY-MM-DD
     * @return array
     * @throws Exception
     */
    public function syncEmployees(string $sinceDate): array
    {
        if (!$this->connected) {
            // Strict enforcement: No mock data allowed.
            throw new RuntimeException("Cannot sync employees: Zucchetti ERP not connected.");
        }

        try {
            $response = $this->soapClient->GetHumanResources([
                'token' => $this->authToken,
                'filter_date' => $sinceDate
            ]);

            // Transform raw SOAP object to standard array
            $employees = [];
            if (!empty($response->GetHumanResourcesResult->Person)) {
                // Handle both single object and array of objects
                $rawList = is_array($response->GetHumanResourcesResult->Person)
                    ? $response->GetHumanResourcesResult->Person
                    : [$response->GetHumanResourcesResult->Person];

                foreach ($rawList as $person) {
                    $employees[] = [
                        'external_id' => (string) $person->Code,
                        'first_name' => (string) $person->Name,
                        'last_name' => (string) $person->Surname,
                        'role' => (string) ($person->JobTitle ?? 'N/A'),
                        'department' => (string) ($person->Department ?? 'General'),
                        'email' => (string) ($person->Email ?? '')
                    ];
                }
            }

            return $employees;

        } catch (Exception $e) {
            error_log("[ZucchettiAdapter] Sync Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pushes attendance data to ERP.
     * 
     * @param array $shiftData
     * @return bool
     */
    public function pushTimeSheet(array $shiftData): bool
    {
        if (!$this->connected) {
            return false;
        }

        try {
            // Construct XML Payload as per Zucchetti spec
            $xmlPayload = $this->buildTimeSheetXml($shiftData);

            $response = $this->soapClient->ImportPresences([
                'token' => $this->authToken,
                'xml_data' => $xmlPayload
            ]);

            return isset($response->ImportPresencesResult) && $response->ImportPresencesResult->success;

        } catch (Exception $e) {
            error_log("[ZucchettiAdapter] Push Error: " . $e->getMessage());
            return false; // Fail safe
        }
    }

    public function getProviderName(): string
    {
        return 'Zucchetti HR Suite (SOAP)';
    }

    // --- Internal Helpers ---

    private function buildTimeSheetXml(array $data): string
    {
        // Real XML construction using XMLWriter for safety/speed
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('AttendanceList');

        $writer->startElement('Record');
        $writer->writeAttribute('EmployeeID', $data['employee_id'] ?? '');
        $writer->writeAttribute('Date', $data['date'] ?? '');
        $writer->writeElement('In', $data['start_time'] ?? '');
        $writer->writeElement('Out', $data['end_time'] ?? '');
        $writer->endElement(); // Record

        $writer->endElement(); // AttendanceList
        return $writer->outputMemory();
    }
}
