<?php

// 1. Load existing file to preserve Foreign Countries (Z codes)
$currentFile = __DIR__ . '/resources/belfiore.json';
if (!file_exists($currentFile)) {
    die("Error: resources/belfiore.json not found.\n");
}
$currentData = json_decode(file_get_contents($currentFile), true);
if (!$currentData) {
    die("Error: Invalid JSON in belfiore.json\n");
}

// Extract Foreign Countries (Z codes)
$foreignCountries = [];
foreach ($currentData as $name => $code) {
    if (strpos($code, 'Z') === 0) {
        $foreignCountries[$name] = $code;
    }
}
echo "Found " . count($foreignCountries) . " foreign countries in existing file.\n";

// 2. Fetch reliable Italian Municipalities List
$url = 'https://raw.githubusercontent.com/matteocontrini/comuni-json/master/comuni.json';
echo "Fetching updated Italian Municipalities list from $url...\n";
$json = file_get_contents($url, false, stream_context_create(['http' => ['header' => 'User-Agent: PHP']]));
if ($json === false) {
    die("Error: Failed to fetch comuni.json\n");
}
$comuniData = json_decode($json, true);
if (!$comuniData) {
    die("Error: Invalid JSON from URL\n");
}

$italianMunicipalities = [];
foreach ($comuniData as $comune) {
    if (isset($comune['nome']) && isset($comune['codiceCatastale'])) {
        $name = mb_strtoupper($comune['nome'], 'UTF-8');
        $code = $comune['codiceCatastale'];
        $italianMunicipalities[$name] = $code;
    }
}
echo "Fetched " . count($italianMunicipalities) . " Italian municipalities.\n";

// 3. Merge
$finalData = array_merge($italianMunicipalities, $foreignCountries);

// 4. Sort by Name
ksort($finalData);

echo "Total entries: " . count($finalData) . "\n";

// 5. Save
// Use unescaped unicode to preserve accented characters correctly
file_put_contents($currentFile, json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Success! resources/belfiore.json updated.\n";
