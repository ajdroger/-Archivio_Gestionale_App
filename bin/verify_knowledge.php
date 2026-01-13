<?php
require __DIR__ . '/../vendor/autoload.php';

use FratellanzaMilitare\AI\Providers\OllamaProvider;
use FratellanzaMilitare\AI\RAG\SimpleVectorStore;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Build Container
$containerBuilder = new \DI\ContainerBuilder();
$definitions = require __DIR__ . '/../config/container.php';
foreach ($definitions as $def) {
    if (file_exists($def)) {
        $containerBuilder->addDefinitions($def);
    }
}
$container = $containerBuilder->build();

// Get services from container
$vectorStore = $container->get(SimpleVectorStore::class);
$provider = $container->get(OllamaProvider::class);

$questions = [
    "Regex" => "Qual è la regex esatta utilizzata nel nuovo approccio di Semantic Chunking descritto nel Changelog v5.2.1?",
    "Redis" => "Perché nell'ADR-016 abbiamo deciso di non usare Redis? Cosa dice il documento?",
    "Growth" => "Qual è la crescita percentuale del valore del software dal prototipo v1.0 alla versione v4.0 Ultimate?",
    "ROTI" => "Qual è il Return on Time Investment (ROTI) calcolato per lo sviluppatore nel report 2026?"
];

foreach ($questions as $key => $query) {
    echo "\n🔎 Testing: $key ($query)\n";
    $embedding = $provider->embed($query);
    // 3. Search
    $results = $vectorStore->search($embedding, 3); // Check Top 3

    if (empty($results)) {
        echo "   ❌ No results found.\n";
    } else {
        $topScore = $results[0]['score'];
        $topSource = $results[0]['metadata']['source_file'] ?? 'unknown';
        echo "   ✅ Top Match: $topSource (Score: " . number_format($topScore, 4) . ")\n";
    }
}
