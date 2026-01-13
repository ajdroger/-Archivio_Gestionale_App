<?php

namespace Tests\Integration;

use Tests\TestCase;
use MCAG\GestioneSoci\Socio;
use MCAG\GestioneSoci\DatiAnagrafici;
use MCAG\Enum\StatoIscrizione;

test('repository handles special characters in search', function () {
    /** @var TestCase $this */
    $repo = new \MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository($this->db);

    $socio = new Socio();
    $socio->CodiceFiscale = 'EDGE_CASE_01';
    $socio->Matricola = 'M_EDGE_1';
    $socio->Stato = StatoIscrizione::ATTIVO;
    $socio->DatiPersonali = new DatiAnagrafici();
    $socio->DatiPersonali->Nome = "O'Connor"; // Name with apostrophe
    $socio->DatiPersonali->Cognome = "D'Amico";
    $socio->DatiPersonali->DataNascita = new \DateTime();
    $socio->DatiPersonali->Indirizzo = "Via Test 1";
    $socio->DatiPersonali->Email = "oconnor@example.com";
    $socio->DatiPersonali->Telefono = "111222333";
    $repo->save($socio);

    // Search with apostrophe
    $results = $repo->search("O'Connor");
    expect($results)->not->toBeEmpty();
    expect($results[0]->DatiPersonali->Nome)->toBe("O'Connor");

    // Search with SQL injection attempt (should be safe thanks to bindings)
    $resultsInj = $repo->search("' OR '1'='1");
    // Should NOT match everything, only exact match if any
    // Since we search LIKE %query%, it will search for names containing that string literal.
    // It should effectively return empty unless a user is named that.
    $all = $repo->findAll();
    expect(count($resultsInj))->toBeLessThan(count($all));
});

test('repository handles unicode characters', function () {
    /** @var TestCase $this */
    $repo = new \MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository($this->db);

    $socio = new Socio();
    $socio->CodiceFiscale = 'UNICODE_02';
    $socio->Matricola = 'M_UNI_2';
    $socio->Stato = StatoIscrizione::ATTIVO;
    $socio->DatiPersonali = new DatiAnagrafici();
    $socio->DatiPersonali->Nome = "Jürgen";
    $socio->DatiPersonali->Cognome = "Müller";
    $socio->DatiPersonali->DataNascita = new \DateTime();
    $socio->DatiPersonali->Indirizzo = "Strasse 1";
    $socio->DatiPersonali->Email = "muller@example.com";
    $socio->DatiPersonali->Telefono = "999888777";
    $repo->save($socio);

    $results = $repo->search("Müller");
    expect($results)->not->toBeEmpty();
    expect($results[0]->DatiPersonali->Cognome)->toBe("Müller");
});
