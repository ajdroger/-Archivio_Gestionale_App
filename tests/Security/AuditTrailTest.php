<?php

use MCAG\SecurityLayer\Amministratore;
use MCAG\SecurityLayer\Operatore;
use MCAG\SecurityLayer\AuditTrail;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    // Database pulito gestito da TestCase::setUp e tearDown
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
});

test('log evento', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    $operatore = new Operatore();
    $operatore->ID = 1;
    $operatore->Username = 'op_audit_test';

    $audit->logEvento($operatore, 'TEST_ACTION', 'resource_123');

    $result = $audit->ricercaAzioni(['action' => 'TEST_ACTION']);
    $eventi = $result['data'];


    expect($eventi)->not->toBeEmpty();
    expect($eventi[0]['username'])->toBe('op_...est');
    expect($eventi[0]['action'])->toBe('TEST_ACTION');
    expect($eventi[0]['resource_id'])->toBe('res...123');
});

test('esportazione json', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_export';

    $audit->logEvento($admin, 'EXPORT_TEST', 'res_456');

    $jsonExport = $audit->esportaLog('json');

    expect($jsonExport)->toBeString()
        ->toContain('EXPORT_TEST')
        ->toContain('adm...ort');

    $decoded = json_decode($jsonExport, true);
    expect($decoded)->toBeArray();
});

test('esportazione csv', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_csv';

    $audit->logEvento($admin, 'CSV_TEST', 'res_789');

    $csvExport = $audit->esportaLog('csv');

    expect($csvExport)->toBeString()
        ->toContain('Timestamp,User ID,Username')
        ->toContain('CSV_TEST')
        ->toContain('admin_csv');
});

test('esportazione formato non supportato throws exception', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();
    expect(fn() => $audit->esportaLog('xml'))->toThrow(InvalidArgumentException::class);
});

test('ricerca azioni con filtri', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    $user1 = new Operatore();
    $user1->ID = 1;
    $user1->Username = 'user1';

    $user2 = new Operatore();
    $user2->ID = 2;
    $user2->Username = 'user2';

    $audit->logEvento($user1, 'LOGIN', 'res1');
    $audit->logEvento($user2, 'LOGOUT', 'res2');
    $audit->logEvento($user1, 'LOGIN', 'res3');

    $resultLogin = $audit->ricercaAzioni(['action' => 'LOGIN']);
    $loginEvents = $resultLogin['data'];
    expect(count($loginEvents))->toBeGreaterThanOrEqual(2);

    $resultUser1 = $audit->ricercaAzioni(['username' => 'user1']);
    $user1Events = $resultUser1['data'];
    expect(count($user1Events))->toBe(2);
});

test('genera report today', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_report';

    $audit->logEvento($admin, 'REPORT_TEST', 'rep_001');
    $audit->logEvento($admin, 'REPORT_TEST', 'rep_002');
    $audit->logEvento($admin, 'LOGIN', 'rep_003');

    $report = $audit->generaReport('today');

    expect($report)->toBeArray()
        ->toHaveKey('periodo')
        ->toHaveKey('totale_eventi')
        ->toHaveKey('azioni_per_tipo')
        ->toHaveKey('utenti_attivi');

    expect($report['periodo'])->toBe('today');
    expect($report['totale_eventi'])->toBeGreaterThan(0);
    expect($report['utenti_attivi'])->toBeGreaterThanOrEqual(1);
});

test('scrittura su database', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    $user = new Operatore();
    $user->ID = 1;
    $user->Username = 'test_user';

    $audit->logEvento($user, 'DB_TEST', 'file_001');

    $result = $audit->ricercaAzioni(['action' => 'DB_TEST']);
    $eventi = $result['data'];
    expect($eventi)->not->toBeEmpty();
    expect($eventi[0]['action'])->toBe('DB_TEST');
});
