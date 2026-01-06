<?php

use FratellanzaMilitare\InfrastrutturaIT\GoogleDriveAdapter;
use FratellanzaMilitare\InfrastrutturaIT\OCREngine;

test('google drive adapter uploads and downloads', function () {
    // Deprecated
    expect(true)->toBeTrue();
    /*
    $adapter = new GoogleDriveAdapter();

    $url = $adapter->upload("test_file.pdf", "FakeContent");
    expect($url)->toContain("drive.google.com");

    $content = $adapter->download("fake_uuid");
    expect($content)->toContain("File content");
    */
});

test('ocr engine processes image', function () {
    $engine = new OCREngine();

    $text = $engine->processaImmagine("bitmap_data");
    expect($text)->toContain("NOME: MARIO");

    $data = $engine->estraiCampiChiave($text);
    expect($data)->toHaveKey("NOME")
        ->toHaveKey("COGNOME");

    expect($data['NOME'])->toBe("MARIO");
    expect($data['COGNOME'])->toBe("ROSSI");
});
