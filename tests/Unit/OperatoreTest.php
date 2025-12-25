<?php

use FratellanzaMilitare\SecurityLayer\Operatore;
use FratellanzaMilitare\SecurityLayer\AuditTrail;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->operatore = new Operatore();
    $this->operatore->ID = 1;
    $this->operatore->Username = 'operatore_test';

    // Pulisci audit log
    $this->logFile = __DIR__ . '/../../logs/audit_trail.json';
    if (file_exists($this->logFile)) {
        unlink($this->logFile);
    }
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
    if (file_exists($this->logFile)) {
        unlink($this->logFile);
    }
});

test('carica pratica', function () {
    /** @var \Tests\TestCase $this */
    $result = $this->operatore->caricaPratica(
        'RSSMRA80A01H501U',
        'Iscrizione',
        ['anno' => 2025, 'quota' => 50.00]
    );

    expect($result)->toBeTrue();

    // Verifica che sia stato loggato
    $audit = AuditTrail::getInstance();
    $eventi = $audit->ricercaAzioni(['action' => 'UPLOAD_DOCUMENT']);
    expect($eventi)->toBeArray()->not->toBeEmpty();
});

test('carica pratica senza codice fiscale throws exception', function () {
    /** @var \Tests\TestCase $this */
    expect(function () {
        /** @var \Tests\TestCase $this */
        $this->operatore->caricaPratica('', 'Iscrizione', []);
    })->toThrow(InvalidArgumentException::class);
});

test('carica pratica senza tipo throws exception', function () {
    /** @var \Tests\TestCase $this */
    expect(function () {
        /** @var \Tests\TestCase $this */
        $this->operatore->caricaPratica('RSSMRA80A01H501U', '', []);
    })->toThrow(InvalidArgumentException::class);
});

test('ricerca socio', function () {
    /** @var \Tests\TestCase $this */
    $risultati = $this->operatore->ricercaSocio('cf', 'RSSMRA80A01H501U');
    expect($risultati)->toBeArray();

    // Verifica logging
    $audit = AuditTrail::getInstance();
    $eventi = $audit->ricercaAzioni(['action' => 'SEARCH_SOCIO']);
    expect($eventi)->toBeArray()->not->toBeEmpty();
});

test('ricerca socio per nome', function () {
    /** @var \Tests\TestCase $this */
    $risultati = $this->operatore->ricercaSocio('nome', 'Mario');
    expect($risultati)->toBeArray();
});

test('ricerca socio per matricola', function () {
    /** @var \Tests\TestCase $this */
    $risultati = $this->operatore->ricercaSocio('matricola', 'MAT123');
    expect($risultati)->toBeArray();
});

test('stampa report', function () {
    /** @var \Tests\TestCase $this */
    $report = $this->operatore->stampaReport('soci_attivi', ['anno' => 2025]);

    expect($report)->toBeString()
        ->toContain('Report Type: soci_attivi')
        ->toContain('operatore_test');

    // Verifica logging
    $audit = AuditTrail::getInstance();
    $eventi = $audit->ricercaAzioni(['action' => 'GENERATE_REPORT']);
    expect($eventi)->toBeArray()->not->toBeEmpty();
});

test('stampa report senza parametri', function () {
    /** @var \Tests\TestCase $this */
    $report = $this->operatore->stampaReport('report_base');

    expect($report)->toBeString()
        ->toContain('Report Type: report_base');
});
