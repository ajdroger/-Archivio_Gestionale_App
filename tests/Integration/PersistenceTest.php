<?php

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo = new PDOSocioRepository($this->db);
});

test('can save and retrieve socio', function () {
    /** @var \Tests\TestCase $this */
    $socio = new Socio();
    $socio->CodiceFiscale = "TESTCF123456";
    $socio->Matricola = "M001";
    $socio->Stato = StatoIscrizione::ATTIVO;

    $dati = new DatiAnagrafici();
    $dati->Nome = "Mario";
    $dati->Cognome = "Rossi";
    $dati->DataNascita = new DateTime("1980-01-01");
    $dati->Indirizzo = "Via Roma 1";
    $dati->Email = "mario.rossi@example.com";
    $socio->DatiPersonali = $dati;

    $this->repo->save($socio);

    $retrieved = $this->repo->findByCodiceFiscale("TESTCF123456");

    expect($retrieved)->not->toBeNull();
    expect($retrieved->DatiPersonali->Nome)->toBe("Mario");
    expect($retrieved->DatiPersonali->Cognome)->toBe("Rossi");

    // Pulizia
    $this->repo->delete("TESTCF123456");
});
