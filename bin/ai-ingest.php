<?php
/**
 * MCAG AI Knowledge Ingestion Script
 * Usage: php bin/ai-ingest.php [--force]
 * 
 * This script scans the codebase and documentation to build/update the local Vector Store JSON.
 * It is designed to be run via Cron or Git Hook to keep the AI Assistant context fresh.
 */

require_once __DIR__ . '/../vendor/autoload.php'; // Ensure vendor exists or handle gracefully

// Configuration
$config = [
    'docs_dir' => __DIR__ . '/../Documentazione',
    'src_dir' => __DIR__ . '/../src',
    'output_file' => __DIR__ . '/../storage/ai/vector_store.json',
    'chunk_size' => 1000, // characters
    'extensions' => ['md', 'php', 'txt']
];

echo "--------------------------------------------------\n";
echo "   MCAG AI Ingest Protocol v1.0                   \n";
echo "   Solo Developer Assistant                       \n";
echo "--------------------------------------------------\n";

if (!is_dir(dirname($config['output_file']))) {
    mkdir(dirname($config['output_file']), 0755, true);
}

// 1. Scan Files
echo "[*] Scanning directories...\n";
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config['docs_dir']));
foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), $config['extensions'])) {
        $files[] = $file->getRealPath();
    }
}
// Add core source code
$iteratorSrc = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config['src_dir']));
foreach ($iteratorSrc as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getRealPath();
    }
}

echo "    Found " . count($files) . " knowledge sources.\n";

// 2. Process & Chunk
echo "[*] Processing content...\n";
$vectors = [];
$totalChunks = 0;

foreach ($files as $filePath) {
    $content = file_get_contents($filePath);
    $relativePath = str_replace(__DIR__ . '/../', '', $filePath);

    // Simple sematic chunking simulation
    // In production: Connect to Ollama/Python script here for embeddings
    $chunks = str_split($content, $config['chunk_size']);

    foreach ($chunks as $index => $chunk) {
        $vectors[] = [
            'id' => md5($relativePath . $index),
            'source' => $relativePath,
            'content' => mb_substr($chunk, 0, 100) . '...', // Preview
            // 'embedding' => [0.123, 0.456, ...] // Placeholder for actual vector
            'timestamp' => time()
        ];
        $totalChunks++;
    }
}

// 3. Save Payload
echo "[*] Saving Vector Store...\n";
// For simulation, we save a lightweight metadata map instead of full content to save space
file_put_contents($config['output_file'], json_encode([
    'meta' => [
        'generated_at' => date('Y-m-d H:i:s'),
        'total_files' => count($files),
        'total_chunks' => $totalChunks
    ],
    'index_sample' => array_slice($vectors, 0, 50)
], JSON_PRETTY_PRINT));

echo "[+] Ingestion Complete. Knowledge Base Updated.\n";
echo "    Target: " . $config['output_file'] . "\n";
echo "--------------------------------------------------\n";
