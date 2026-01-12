<?php

use FratellanzaMilitare\AI\RAG\SimpleVectorStore;
use FratellanzaMilitare\AI\Providers\OllamaProvider;
use Psr\Log\NullLogger;

test('SimpleVectorStore stores and retrieves documents', function () {
    $storeFile = __DIR__ . '/test_vector_store.json';
    if (file_exists($storeFile))
        unlink($storeFile);

    $store = new SimpleVectorStore(new NullLogger(), $storeFile);

    // Add Documents
    $store->addDocument('doc1', 'La Fratellanza è stata fondata nel 1900.', [0.1, 0.2, 0.3]);
    $store->addDocument('doc2', 'Il presidente attuale è Mario Rossi.', [0.4, 0.5, 0.6]);

    // Search
    $results = $store->search([0.1, 0.2, 0.3]); // Perfect match query

    expect($results)->toHaveCount(2);
    expect($results[0]['id'])->toBe('doc1');
    expect($results[0]['score'])->toBeGreaterThan(0.99);

    if (file_exists($storeFile))
        unlink($storeFile);
});

test('OllamaProvider handles unavailability gracefully', function () {
    $provider = new OllamaProvider(new NullLogger(), 'http://invalid-host:1234');

    expect($provider->isAvailable())->toBeFalse();

    // Generate should return fallback message
    $response = $provider->generate('Ciao');
    expect($response)->toContain('Ollama non è raggiungibile');

    // Embed should return mock vector
    $embedding = $provider->embed('Test');
    expect($embedding)->toBeArray();
});
