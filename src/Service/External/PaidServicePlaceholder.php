<?php

namespace MCAG\Service\External;

/**
 * Placeholder for Paid Services Integration.
 *
 * This abstract class serves as a base for all external service integrations
 * that require a paid subscription. It ensures strict encapsulation and
 * avoids runtime errors when services are not active.
 */
abstract class PaidServicePlaceholder
{
    protected bool $enabled;
    protected string $serviceName;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;
        $this->enabled = filter_var($_ENV['ENABLE_PAID_SERVICES'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Checks if the service executes logic or acts as a dummy.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Logs an initialization message securely.
     */
    protected function logInit(): void
    {
        if ($this->enabled) {
            // Actual logger integration would go here
            // error_log("[$this->serviceName] Service Initialized.");
        }
    }
}


