<?php

use FratellanzaMilitare\SecurityLayer\Amministratore;
use FratellanzaMilitare\SecurityLayer\Operatore;
use FratellanzaMilitare\SecurityLayer\AccessControlList;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->acl = new AccessControlList();
});

test('operatore ha permessi base', function () {
    /** @var \Tests\TestCase $this */
    $operatore = new Operatore();
    $operatore->ID = 1;
    $operatore->Username = 'operatore_test';

    expect($this->acl->verificaPermesso($operatore, 'soci.read'))->toBeTrue();
    expect($this->acl->verificaPermesso($operatore, 'documenti.create'))->toBeTrue();
    expect($this->acl->verificaPermesso($operatore, 'soci.update'))->toBeTrue();
    expect($this->acl->verificaPermesso($operatore, 'report.generate'))->toBeTrue();
});

test('operatore non ha permessi amministrativi', function () {
    /** @var \Tests\TestCase $this */
    $operatore = new Operatore();
    $operatore->ID = 2;
    $operatore->Username = 'operatore_limitato';

    expect($this->acl->verificaPermesso($operatore, 'admin.delete_all'))->toBeFalse();
    expect($this->acl->verificaPermesso($operatore, 'admin.create_user'))->toBeFalse();
    expect($this->acl->verificaPermesso($operatore, 'system.config'))->toBeFalse();
});

test('amministratore ha accesso completo', function () {
    /** @var \Tests\TestCase $this */
    $admin = new Amministratore();
    $admin->ID = 1;
    $admin->Username = 'admin_full';

    expect($this->acl->verificaPermesso($admin, 'soci.read'))->toBeTrue();
    expect($this->acl->verificaPermesso($admin, 'soci.delete'))->toBeTrue();
    expect($this->acl->verificaPermesso($admin, 'any.resource'))->toBeTrue();
    expect($this->acl->verificaPermesso($admin, 'admin.dangerous_action'))->toBeTrue();
});

test('grant permesso a nuovo ruolo', function () {
    /** @var \Tests\TestCase $this */
    $this->acl->grant('Supervisore', 'soci.read');
    $this->acl->grant('Supervisore', 'report.generate');

    $permessi = $this->acl->getPermessi('Supervisore');
    expect($permessi)->toContain('soci.read')
        ->toContain('report.generate');
});

test('grant non aggiunge duplicati', function () {
    /** @var \Tests\TestCase $this */
    $this->acl->grant('TestRole', 'test.permission');
    $this->acl->grant('TestRole', 'test.permission'); // Duplicato

    $permessi = $this->acl->getPermessi('TestRole');
    $occorrenze = array_count_values($permessi);
    expect($occorrenze['test.permission'])->toBe(1);
});
