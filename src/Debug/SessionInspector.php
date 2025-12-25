<?php

namespace FratellanzaMilitare\Debug;

class SessionInspector
{
    /**
     * Returns a safe-to-view array of session data.
     * Sensitive fields are masked.
     */
    public static function inspect(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            return ['status' => 'NONE', 'message' => 'No active session'];
        }

        $safeData = [];
        $sensitiveKeys = ['password', 'secret', 'token', 'csrf_value'];

        foreach ($_SESSION as $key => $value) {
            if (self::isSensitive($key)) {
                $safeData[$key] = '********';
            } elseif (is_object($value)) {
                $safeData[$key] = '[Object] ' . get_class($value);
            } elseif (is_array($value)) {
                $safeData[$key] = '[Array] count=' . count($value);
            } else {
                $safeData[$key] = $value;
            }
        }

        return [
            'status' => 'ACTIVE',
            'id' => session_id(),
            'name' => session_name(),
            'data' => $safeData,
            'cookie_params' => session_get_cookie_params(),
            'meta' => [
                'started_at' => null, // Session start time not standard in PHP
                'last_activity' => $_SESSION['last_activity'] ?? 'N/A'
            ]
        ];
    }

    private static function isSensitive(string $key): bool
    {
        $patterns = ['/pass/', '/token/', '/secret/', '/auth/', '/csrf/'];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, strtolower($key))) {
                return true;
            }
        }
        return false;
    }
}
