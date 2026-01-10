<?php

use FratellanzaMilitare\Service\ValidationService;

test('ValidationService individua spoofing estensione', function () {
    $service = new ValidationService();

    // Challenge 1: Un file di testo con estensione .jpg
    $fakeJpg = tempnam(sys_get_temp_dir(), 'test_spoof');
    file_put_contents($fakeJpg, '<?php echo "shell"; ?>');

    // Il controllo Magic Bytes dovrebbe fallire perché inizia con <?php, non FF D8 FF (JPEG)
    // Anche se il server (finfo) potrebbe rilevarlo come text/x-php o text/plain
    expect($service->validateRealMimeType($fakeJpg))->toBeFalse();

    // Challenge 2: Un vero JPEG
    $realJpg = tempnam(sys_get_temp_dir(), 'test_real');
    $jpegHeader = hex2bin('FFD8FFE000104A4649460001'); // Standard JPEG header
    file_put_contents($realJpg, $jpegHeader);

    expect($service->validateRealMimeType($realJpg))->toBeTrue();

    unlink($fakeJpg);
    unlink($realJpg);
});

test('ValidationService accetta file ZIP per compatibilità Office', function () {
    $service = new ValidationService();

    // Challenge 3: Un file ZIP (simulazione .docx)
    $zipFile = tempnam(sys_get_temp_dir(), 'test_zip');

    // Create valid zip using base64 (since ZipArchive might be missing or finfo strict)
    $zipContent = base64_decode('UEsDBBQAAAAIAAAAIQA8Wl0jCwAAAAwAAAAIAAAAdGVzdC50eHR0ZXN0IGNvbnRlbnRQSwECFAAUAAAACAAAACEAPFpdIwsAAAAMAAAACAAAAAAAAAABACAAAAAAAAAAdGVzdC50eHRQSwUGAAAAAAEAAQAzAAAANAAAAAAA');
    file_put_contents($zipFile, $zipContent);

    expect($service->validateRealMimeType($zipFile))->toBeTrue(); // Accettato come application/zip

    unlink($zipFile);
});
