<?php

namespace FratellanzaMilitare\Infrastructure\Alerting;

/**
 * Client per PagerDuty API (v2 Events).
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

        $ch = curl_init('https://events.pagerduty.com/v2/enqueue');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/vnd.pagerduty+json;version=2'
        ]);

        // Timeout per non bloccare l'app
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 202;
    }
}
