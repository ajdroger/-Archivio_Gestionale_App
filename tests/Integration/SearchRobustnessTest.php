<?php

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo = new PDOSocioRepository($this->db);

    // Create a seed socio
    $socio = new Socio();
    $socio->CodiceFiscale = "BRNMRA80A01H501U";
    $socio->Matricola = "M999";
    $socio->Stato = StatoIscrizione::ATTIVO;

    $dati = new DatiAnagrafici();
    $dati->Nome = "MARIO";
    $dati->Cognome = "BRUNI";
    $dati->DataNascita = new DateTime("1980-01-01");
    $dati->Email = "mario.bruni@example.com";
    $dati->Telefono = "123456789";
    $dati->Indirizzo = "Via Test 1";
    $socio->DatiPersonali = $dati;

    $this->repo->save($socio);
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo->delete("BRNMRA80A01H501U");
});

test('search finds by first name only', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("MARIO");
    expect($results)->not->toBeEmpty();

    // Check if our seeded user is in the results (there might be others like Mario Rossi)
    $found = array_filter($results, fn($s) => $s->CodiceFiscale === "BRNMRA80A01H501U");
    expect($found)->not->toBeEmpty();
});

test('search finds by last name only', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("BRUNI");
    expect($results)->not->toBeEmpty();
});

test('search finds by combined first and last name', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("MARIO BRUNI");
    expect($results)->not->toBeEmpty();
});

test('search finds by combined last and first name', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("BRUNI MARIO");
    expect($results)->not->toBeEmpty();
});

test('search finds by partial codice fiscale', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("BRNMRA");
    expect($results)->not->toBeEmpty();
});

test('search finds by full codice fiscale', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("BRNMRA80A01H501U");
    expect($results)->not->toBeEmpty();
});

test('search finds by matricola', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("M999");
    expect($results)->not->toBeEmpty();
});

test('search finds by email', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("mario.bruni@example.com");
    expect($results)->not->toBeEmpty();
});

test('search finds by phone', function () {
    /** @var \Tests\TestCase $this */
    $results = $this->repo->search("123456789");
    expect($results)->not->toBeEmpty();
});
