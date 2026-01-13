<?php

use MCAG\GestioneSoci\ModuloIscrizione;

test('verifica integrita documento', function () {
    $doc = new ModuloIscrizione();
    $content = "Contenuto del file di prova";
    $doc->HashSHA256 = hash('sha256', $content);

    // Contenuto corretto
    expect($doc->verificaIntegrita($content))->toBeTrue();

    // Contenuto errato
    expect($doc->verificaIntegrita("Contenuto modificato"))->toBeFalse();

    // Nessun contenuto passato
    expect($doc->verificaIntegrita(null))->toBeFalse();
});
