<?php
// Simulate Traffic
$baseUrl = 'http://localhost/MCAG_Militare-Civile-Archivio-Gestionale/public';

$scenarios = [
    ['path' => '/', 'method' => 'GET', 'ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'code' => 200],
    ['path' => '/login', 'method' => 'POST', 'ua' => 'Mozilla/5.0', 'code' => 200],
    ['path' => '/api/v1/soci?search=UNION+SELECT', 'method' => 'GET', 'ua' => 'EvilBot/1.0', 'code' => 403], // SQLi
    ['path' => '/wp-admin/setup-config.php', 'method' => 'GET', 'ua' => 'Python-urllib/3.8', 'code' => 404], // Scanning
    ['path' => '/contact?msg=<script>alert(1)</script>', 'method' => 'POST', 'ua' => 'Mozilla/5.0', 'code' => 200], // XSS
];

echo "--- STARTING SIMULATION ---\n";

foreach ($scenarios as $s) {
    $url = $baseUrl . $s['path'];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $s['method']);
    curl_setopt($ch, CURLOPT_USERAGENT, $s['ua']);
    // For local dev, we might need to ignore SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Request to {$s['path']} -> HTTP $httpCode\n";
    usleep(200000); // 200ms delay
}

echo "--- SIMULATION COMPLETE ---\n";
echo "Checking API pulse...\n";

$pulse = file_get_contents($baseUrl . '/api/public/security/pulse?reset_geo=1');
$data = json_decode($pulse, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "API ERROR: Invalid JSON response.\n";
    echo substr($pulse, 0, 500);
} else {
    echo "API SUCCESS. Found " . count($data) . " threats.\n";
    foreach ($data as $t) {
        echo "- [{$t['timestamp']}] {$t['ip']} ({$t['details']['risk_level']}) - {$t['msg']}\n";
    }
}
