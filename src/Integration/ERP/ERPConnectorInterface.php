<?php

namespace MCAG\Integration\ERP;

interface ERPConnectorInterface
{
    /**
     * Authenticate with the remote ERP system.
     * @return bool True if connected successfully.
     */
    public function connect(): bool;

    /**
     * Fetch all employees modified since a specific date.
     * @param string $sinceDate ISO 8601 Date
     * @return array List of employee data normalized for MCAG.
     */
    public function syncEmployees(string $sinceDate): array;

    /**
     * Push a workshift record to the ERP (e.g., for payroll).
     * @param array $shiftData
     * @return bool
     */
    public function pushTimeSheet(array $shiftData): bool;

    /**
     * Get the name of the ERP provider.
     */
    public function getProviderName(): string;
}
