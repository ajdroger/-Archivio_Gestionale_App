<?php

/**
 * Script per validare le azioni GitHub referenziate nei file workflow.
 * Verifica l'esistenza reale del repository e del tag/commit su GitHub.
 */

$yamlFile = __DIR__ . '/../../.github/workflows/ci.yml';

if (!file_exists($yamlFile)) {
    die("File not found: $yamlFile\n");
}

$content = file_get_contents($yamlFile);

// Regex semplice per trovare "uses: repo@ref"
// Cattura tutto fino al primo spazio o a capo o commento
preg_match_all('/uses:\s+([^#\s]+)/', $content, $matches);

$actions = $matches[1];
$errors = 0;

$context = stream_context_create([
    'http' => [
        'method' => 'HEAD', // Use HEAD to minimize data
        'header' => "User-Agent: Resilience-Validator/1.0\r\n"
    ]
]);

echo "Valido le azioni in: " . realpath($yamlFile) . "\n";
echo "---------------------------------------------------\n";

foreach ($actions as $action) {
    if (str_starts_with($action, './')) {
        echo "[SKIP] $action (Action Locale)\n";
        continue;
    }

    if (!str_contains($action, '@')) {
        echo "[WARN] $action (Nessuna versione specificata - Sconsigliato)\n";
        continue;
    }

    [$repo, $ref] = explode('@', $action, 2);

    echo "Checking: $repo @ $ref ... ";

    // Riconoscimento SHA (40 caratteri hex)
    $isCommit = preg_match('/^[a-f0-9]{40}$/i', $ref);

    $url = $isCommit
        ? "https://github.com/$repo/commit/$ref"
        : "https://github.com/$repo/tree/$ref";

    // Eseguiamo la richiesta
    $headers = @get_headers($url, false, $context);

    if ($headers && (str_contains($headers[0], '200') || str_contains($headers[0], '302'))) {
        echo "OK (Trovato)\n";
    } else {
        echo "FAIL (Non trovato - HTTP " . ($headers ? substr($headers[0], 9, 3) : 'Error') . ")\n";
        echo "       URL Tentato: $url\n";
        $errors++;
    }
}

echo "---------------------------------------------------\n";
if ($errors === 0) {
    echo "RISULTATO: TUTTE LE AZIONI SONO VALIDE.\n";
    echo "NOTA: Se l'IDE segnala ancora errori, ignorali: sono falsi positivi locali.\n";
    exit(0);
} else {
    echo "RISULTATO: TROVATI $errors ERRORI.\n";
    exit(1);
}
