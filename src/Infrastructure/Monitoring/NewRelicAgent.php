<?php

namespace MCAG\Infrastructure\Monitoring;

/**
 * Wrapper per Agente APM (New Relic / Datadog).
 * 
 * TODO: Decommentare e implementare SDK reale.
 */
class NewRelicAgent
{
    public function recordCustomEvent(string $eventType, array $attributes): void
    {
        if (extension_loaded('newrelic') && function_exists('newrelic_record_custom_event')) {
            newrelic_record_custom_event($eventType, $attributes);
        }
    }

    public function noticeError(\Throwable $t): void
    {
        if (extension_loaded('newrelic') && function_exists('newrelic_notice_error')) {
            newrelic_notice_error($t->getMessage(), $t);
        }
    }
}


