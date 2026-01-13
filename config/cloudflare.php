<?php

/**
 * Cloudflare WAF Configuration
 * 
 * TODO: Decommentare e configurare i valori reali quando il servizio sarà attivo.
 */

return [
    'enabled' => filter_var($_ENV['CLOUDFLARE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),

    'api_token' => $_ENV['CLOUDFLARE_API_TOKEN'] ?? '',
    'zone_id' => $_ENV['CLOUDFLARE_ZONE_ID'] ?? '',

    /*
    'waf_rules' => [
        [
            'action' => 'block',
            'description' => 'Block SQL Injection attempts',
            'expression' => '(http.request.uri.path contains "union" and http.request.uri.path contains "select")'
        ],
        [
            'action' => 'challenge', // JS Challenge
            'description' => 'Challenge suspicious User Agents',
            'expression' => '(http.user_agent contains "curl" or http.user_agent contains "wget" or http.user_agent contains "python")'
        ]
    ],

    'ip_whitelist' => [
        '127.0.0.1', // Localhost
        // '192.168.1.100', // Ufficio Amministrazione
    ]
    */
];


