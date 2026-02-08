<?php
$url = 'https://raw.githubusercontent.com/matteocontrini/comuni-json/master/comuni.json';
$content = file_get_contents($url, false, stream_context_create(['http' => ['header' => 'User-Agent: PHP']]));
if ($content === false) {
    echo "Failed to fetch URL\n";
    exit(1);
}
$data = json_decode($content, true);
echo "Count: " . count($data) . "\n";
echo "First 5:\n";
print_r(array_slice($data, 0, 5));

// Check for Z codes
$zCodes = array_filter($data, function ($c) {
    return isset($c['codiceCatastale']) && strpos($c['codiceCatastale'], 'Z') === 0;
});
echo "Z Codes Count: " . count($zCodes) . "\n";
if (count($zCodes) > 0) {
    echo "First 5 Z:\n";
    print_r(array_slice($zCodes, 0, 5));
} else {
    echo "No Z codes found.\n";
}
