<?php

namespace FratellanzaMilitare\Infrastructure\Alerting;

/**
 * Client per PagerDuty API (v2 Events).
 * 
 * TODO: Implementare chiamate HTTP reali verso https://events.pagerduty.com/v2/enqueue
 */
class PagerDutyClient
{
    private string $routingKey;

    public function __construct(string $routingKey = '')
    {
        $this->routingKey = $routingKey ?: ($_ENV['PAGERDUTY_ROUTING_KEY'] ?? '');
    }

    public function sendCriticalAlert(string $summary, string $source, array $details = []): bool
    {
        if (empty($this->routingKey)) {
            return false;
        }

        /*
        $payload = [
            'routing_key' => $this->routingKey,
            'event_action' => 'trigger',
            'payload' => [
                'summary' => $summary,
                'severity' => 'critical',
                'source' => $source,
                'custom_details' => $details
            ]
        ];

        // Eseguire POST request...
        */
        return true;
    }
}
