<?php

use FratellanzaMilitare\SecurityLayer\Amministratore;
use FratellanzaMilitare\SecurityLayer\Operatore;
use FratellanzaMilitare\SecurityLayer\AccessControlList;
use FratellanzaMilitare\SecurityLayer\AuditTrail;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
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

test('workflow creazione utente e operazioni', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();
    $acl = new AccessControlList();

    // 1. Amministratore crea un nuovo operatore
    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_principale';

    $uniqueName = 'nuovo_operatore_' . uniqid();
    $newUserId = $admin->creaUtente($uniqueName, 'Password123!', 'Operatore');
    expect($newUserId)->toBeGreaterThan(0);

    // 2. Verifica che la creazione sia stata loggata
    $resultCreazione = $audit->ricercaAzioni(['action' => 'CREATE_USER']);
    $eventiCreazione = $resultCreazione['data'];
    expect($eventiCreazione)->toBeArray()->not->toBeEmpty();

    // 3. Simula il nuovo operatore che esegue operazioni
    $operatore = new Operatore();
    $operatore->ID = $newUserId;
    $operatore->Username = $uniqueName;

    // 4. Verifica che l'operatore abbia i permessi corretti
    expect($acl->verificaPermesso($operatore, 'soci.read'))->toBeTrue();
    expect($acl->verificaPermesso($operatore, 'documenti.create'))->toBeTrue();
    expect($acl->verificaPermesso($operatore, 'admin.delete_all'))->toBeFalse();

    // 5. Operatore carica un documento
    $result = $operatore->caricaPratica('RSSMRA80A01H501U', 'Iscrizione', ['anno' => 2025]);
    expect($result)->toBeTrue();

    // 6. Amministratore visualizza l'audit log
    $logs = $admin->visualizzaAuditLog();
    expect(count($logs))->toBeGreaterThanOrEqual(2); // CREATE_USER + UPLOAD_DOCUMENT

    // 7. Genera report audit
    $report = $admin->generaReportAudit('today');
    expect($report['utenti_attivi'])->toBeGreaterThanOrEqual(2);
});

test('workflow verifica permessi negati', function () {
    /** @var \Tests\TestCase $this */
    $operatore = new Operatore();
    $operatore->ID = 2;
    $operatore->Username = 'op_limitato';

    $acl = new AccessControlList();

    // L'operatore non dovrebbe avere permessi amministrativi
    expect($acl->verificaPermesso($operatore, 'admin.create_user'))->toBeFalse();
    expect($acl->verificaPermesso($operatore, 'system.shutdown'))->toBeFalse();
});

test('workflow export audit trail', function () {
    /** @var \Tests\TestCase $this */
    $audit = AuditTrail::getInstance();

    // Simula diverse azioni di utenti
    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_export';

    $operatore = new Operatore();
    $operatore->ID = 2;
    $operatore->Username = 'op_export';

    $audit->logEvento($admin, 'LOGIN', 'session_1');
    $audit->logEvento($operatore, 'SEARCH_SOCIO', 'search_1');
    $audit->logEvento($admin, 'CREATE_USER', 'user_new');
    $audit->logEvento($operatore, 'UPLOAD_DOCUMENT', 'doc_1');

    // Export JSON
    $jsonExport = $audit->esportaLog('json');
    expect($jsonExport)->toBeString();
    $data = json_decode($jsonExport, true);
    expect(count($data))->toBeGreaterThanOrEqual(4);

    // Export CSV
    $csvExport = $audit->esportaLog('csv');
    expect($csvExport)->toBeString();
    $lines = explode("\n", trim($csvExport));
    expect(count($lines))->toBeGreaterThanOrEqual(5); // Header + almeno 4 events
});

test('workflow revoca permessi', function () {
    /** @var \Tests\TestCase $this */
    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_revoke';

    $operatoreId = 100;

    // Simula revoca permesso
    $admin->revocaPermessi($operatoreId, 'documenti.delete');

    // Verifica che sia stato loggato
    $audit = AuditTrail::getInstance();
    $result = $audit->ricercaAzioni(['action' => 'REVOKE_PERMISSION']);
    $eventi = array_values($result['data']);

    expect($eventi)->not->toBeEmpty();

    // Verifica che l'evento più recente contenga documenti.delete mascherato
    $lastEvent = end($eventi);
    expect($lastEvent['resource_id'])->toContain('use...ete');
});

test('workflow amministratore accesso totale', function () {
    /** @var \Tests\TestCase $this */
    $acl = new AccessControlList();

    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'super_admin';

    // L'admin può fare tutto (wildcard *)
    $risorse = [
        'soci.read',
        'soci.delete',
        'documenti.create',
        'documenti.delete',
        'admin.create_user',
        'admin.delete_user',
        'system.config',
        'any.custom.resource'
    ];

    foreach ($risorse as $risorsa) {
        expect($acl->verificaPermesso($admin, $risorsa))->toBeTrue();
    }
});
