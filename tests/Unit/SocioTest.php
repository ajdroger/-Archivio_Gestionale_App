<?php

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\Enum\StatoDocumento;
use FratellanzaMilitare\GestioneSoci\ModuloIscrizione;

test('socio can be instantiated', function () {
    $socio = new Socio();
    $socio->CodiceFiscale = "RSSMRA80A01H501U";
    $socio->Stato = StatoIscrizione::ATTIVO;

    expect($socio)->toBeInstanceOf(Socio::class);
    expect($socio->Stato)->toBe(StatoIscrizione::ATTIVO);
});

test('aggiorna anagrafica', function () {
    $socio = new Socio();
    $vecchiDati = new DatiAnagrafici();
    $vecchiDati->Nome = "Mario";
    $socio->DatiPersonali = $vecchiDati;

    $nuoviDati = new DatiAnagrafici();
    $nuoviDati->Nome = "Luigi"; // Cambia il nome

    $socio->aggiornaAnagrafica($nuoviDati);

    expect($socio->DatiPersonali->Nome)->toBe("Luigi");
});

test('verifica morosita', function () {
    $socio = new Socio();

    // Caso 1: Nessun documento -> Moroso
    expect($socio->verificaMorosita())->toBeTrue();

    // Caso 2: Pagato per l'anno precedente -> Moroso
    $iscrizioneVecchia = new ModuloIscrizione();
    $iscrizioneVecchia->AnnoSolare = (int) date('Y') - 1;
    $iscrizioneVecchia->Stato = StatoDocumento::VALIDATO;
    $socio->DocumentiAssociati[] = $iscrizioneVecchia;

    expect($socio->verificaMorosita())->toBeTrue();

    // Caso 3: Pagato per l'anno corrente ma in attesa -> Moroso
    $iscrizioneAttuale = new ModuloIscrizione();
    $iscrizioneAttuale->AnnoSolare = (int) date('Y');
    $iscrizioneAttuale->Stato = StatoDocumento::IN_ATTESA;
    $socio->DocumentiAssociati[] = $iscrizioneAttuale;

    expect($socio->verificaMorosita())->toBeTrue();

    // Caso 4: Pagato e Validato -> Non Moroso
    $iscrizioneAttuale->Stato = StatoDocumento::VALIDATO;
    expect($socio->verificaMorosita())->toBeFalse();
});
