<?php
require __DIR__ . '/../vendor/autoload.php';

use MCAG\AI\RAG\SimpleVectorStore;
use MCAG\AI\Providers\OllamaProvider;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Build Container
$containerBuilder = new ContainerBuilder();
$definitions = require __DIR__ . '/../config/container.php';
foreach ($definitions as $def) {
    if (file_exists($def)) {
        $containerBuilder->addDefinitions($def);
    }
}
$container = $containerBuilder->build();

echo "🔍 Debugging RAG Retrieval...\n";

try {
    $vectorStore = $container->get(SimpleVectorStore::class);
    $ollama = $container->get(OllamaProvider::class); // Needed for query embedding

    // We need to verify if the vector store actually has the new documents
    $storePath = __DIR__ . '/../storage/ai/vector_store.json';
    echo "📂 Store Path: $storePath\n";

    if (!file_exists($storePath)) {
        die("❌ Vector Store file NOT FOUND!\n");
    }

    $storeData = json_decode(file_get_contents($storePath), true);
    $docCount = count($storeData);
    echo "📊 Total Embedded Chunks: $docCount\n";

    // Check for specific content presence in raw store
    $foundV52 = false;
    $foundOmni = false;
    foreach ($storeData as $chunk) {
        $text = $chunk['content'] ?? '';
        if (str_contains($text, '5.2.0'))
            $foundV52 = true;
        if (str_contains($text, 'Omni-Reader'))
            $foundOmni = true;
    }
    echo "RAW CHECK '5.2.0': " . ($foundV52 ? "✅ Found" : "❌ NOT Found") . "\n";
    echo "RAW CHECK 'Omni-Reader': " . ($foundOmni ? "✅ Found" : "❌ NOT Found") . "\n";

    // Test Retrieval for specific query
    $queries = [
        "Quali sono le nuove funzionalità introdotte nella versione 5.2.0 Omni-Reader?",
        "Spiegami l'ADR-029",
        "Come funziona la nuova architettura Omni-Reader?",
        "Perché abbiamo scelto di non usare Redis per le code?"
    ];

    echo "\n🧠 Testing Retrieval...\n";
    foreach ($queries as $q) {
        echo "\n❓ Query: '$q'\n";
        // Convert query to embedding
        $queryEmbedding = $ollama->embed($q);
        // Search
        $results = $vectorStore->search($queryEmbedding, 3); // Top 3

        if (empty($results)) {
            echo "   ❌ No results found.\n";
        } else {
            foreach ($results as $i => $res) {
                $score = number_format($res['score'], 4);
                $content = $res['content'] ?? '';
                $meta = $res['metadata'] ?? [];
                $title = $meta['title'] ?? 'Unknown';

                $preview = substr(str_replace(["\n", "\r"], " ", $content), 0, 100);
                echo "   [$i] Score: $score | Ref: $title | '$preview...'\n";

                if ($i === 0) {
                    echo "      DEBUG STRUCTURE: " . json_encode($res) . "\n";
                }
            }
        }
    }

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

