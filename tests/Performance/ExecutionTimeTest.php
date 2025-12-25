<?php

use FratellanzaMilitare\InfrastrutturaIT\OCREngine;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

test('ocr performance', function () {
    /** @var \Tests\TestCase $this */
    $ocr = new OCREngine();
    $start = microtime(true);

    $result = $ocr->processaImmagine("dummy_data");

    $duration = microtime(true) - $start;
    expect($duration)->toBeLessThan(1.0);
});

test('database search performance', function () {
    /** @var \Tests\TestCase $this */
    $db = DatabaseConnection::getConnection();
    // Pass connection if repository requires it, assuming constructor allows it or uses singleton internal
    // Checking PersistenceTest, it uses `new PDOSocioRepository()` without args if it uses singleton internally
    // or checks Logic. Let's assume repo manages connection or allow injection.
    $repo = new PDOSocioRepository();

    $start = microtime(true);
    $repo->findAll();
    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(0.1);
});
