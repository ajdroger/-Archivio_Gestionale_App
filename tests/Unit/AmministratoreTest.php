<?php

use MCAG\SecurityLayer\Amministratore;
use MCAG\SecurityLayer\AuditTrail;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->admin = new Amministratore();
    $this->admin->ID = 1;
    $this->admin->Username = 'admin_test';

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

test('crea utente', function () {
    /** @var \Tests\TestCase $this */
    $uniqueName = 'nuovo_operatore_' . uniqid();
    $newUserId = $this->admin->creaUtente($uniqueName, 'Password123!', 'Operatore');

    expect($newUserId)->toBeInt()->toBeGreaterThan(0);

    // Verifica che sia stato loggato
    $audit = AuditTrail::getInstance();
    $eventi = $audit->ricercaAzioni(['action' => 'CREATE_USER']);
    expect($eventi)->toBeArray()->not->toBeEmpty();
});

test('models', function () {
    /** @var \Tests\TestCase $this */
    expect($this->admin)->toBeInstanceOf(Amministratore::class);
});

test('crea utente amministratore', function () {
    /** @var \Tests\TestCase $this */
    $uniqueName = 'nuovo_admin_' . uniqid();
    $newUserId = $this->admin->creaUtente($uniqueName, 'SecurePass456!', 'Amministratore');

    expect($newUserId)->toBeInt()->toBeGreaterThan(0);
});

test('revoca permessi', function () {
    /** @var \Tests\TestCase $this */
    $userId = 123;
    $permesso = 'soci.delete';

    $this->admin->revocaPermessi($userId, $permesso);

    // Verifica logging
    $audit = AuditTrail::getInstance();
    $eventi = $audit->ricercaAzioni(['action' => 'REVOKE_PERMISSION']);
    expect($eventi)->toBeArray()->not->toBeEmpty();
});

test('visualizza audit log', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();
    $audit->logEvento($this->admin, 'VIEW_LOG', 'log_001');

    $logs = $this->admin->visualizzaAuditLog(['action' => 'VIEW_LOG']);

    expect($logs)->toBeArray()->not->toBeEmpty();
});

test('visualizza audit log senza filtri', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();
    $audit->logEvento($this->admin, 'ACTION1', 'res1');
    $audit->logEvento($this->admin, 'ACTION2', 'res2');

    $logs = $this->admin->visualizzaAuditLog();

    expect($logs)->toBeArray();
    expect(count($logs))->toBeGreaterThanOrEqual(2);
});

test('genera report audit', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();
    $audit->logEvento($this->admin, 'REPORT_ACTION', 'rep_001');

    $report = $this->admin->generaReportAudit('today');

    expect($report)->toBeArray()
        ->toHaveKey('periodo')
        ->toHaveKey('totale_eventi');
    expect($report['periodo'])->toBe('today');
});
