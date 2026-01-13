<?php

use MCAG\GestioneSoci\Socio;
use MCAG\GestioneSoci\DatiAnagrafici;

test('empty dati personali throws error on access', function () {
    $socio = new Socio();
    $socio->DatiPersonali = new DatiAnagrafici();

    // Accessing uninitialized typed property throws Error
    expect(fn () => $socio->CodiceFiscale)->toThrow(Error::class);

    // Nested verify
    expect(fn () => $socio->DatiPersonali->Nome)->toThrow(Error::class);
});

test('future birth date is allowed for now', function () {
    $dati = new DatiAnagrafici();
    $dati->Nome = 'Future';
    $dati->Cognome = 'Man';
    $dati->DataNascita = new DateTime('+10 years');

    expect($dati->DataNascita)->toBeInstanceOf(DateTime::class);
});

test('invalid codice fiscale length allowed for now', function () {
    $socio = new Socio();
    $socio->CodiceFiscale = "SHORT";

    expect($socio->CodiceFiscale)->toBe("SHORT");
});
