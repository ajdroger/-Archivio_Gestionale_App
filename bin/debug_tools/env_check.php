<?php
/**
 * Fratellanza Militare - Environment Check
 * Analisi variabili d'ambiente e headers.
 */

header('Content-Type: text/plain');

echo "=== ENVIRONMENT CHECK ===\n\n";

echo "--- SERVER INFO ---\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";

echo "\n--- CLIENT INFO ---\n";
echo "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}

echo "\n--- PHP SETTINGS ---\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
