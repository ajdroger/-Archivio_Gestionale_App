<?php
/**
 * API Endpoint to get the latest Benchmark Report filenames.
 * Returns JSON: { "html": "filename.html", "pdf": "filename.pdf" }
 */
header('Content-Type: application/json');

function getLatestBenchmark($dir, $ext)
{
    if (!is_dir($dir))
        return null;

    // Pattern match standard filename format
    $files = glob($dir . "/MCAG_Benchmark_*." . $ext);
    if (!$files || empty($files))
        return null;

    // Sort descending by naturally comparing filenames (v8.3 > v5.4)
    // Using natural sort handles standard versioning reasonably well
    usort($files, function ($a, $b) {
        return strnatcmp(basename($b), basename($a));
    });

    return basename($files[0]);
}

$reportDir = __DIR__ . '/../reports';

// Find latest files
$latestHtml = getLatestBenchmark($reportDir, 'html');
$latestPdf = getLatestBenchmark($reportDir, 'pdf');

echo json_encode([
    'html' => $latestHtml,
    'pdf' => $latestPdf
]);
