<?php

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->db = DatabaseConnection::getConnection();
});

test('required tables exist', function () {
    /** @var \Tests\TestCase $this */
    $requiredTables = ['soci', 'documenti', 'users', 'audit_logs'];

    $stmt = $this->db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($requiredTables as $table) {
        expect($existingTables)->toContain($table);
    }
});

test('socio table schema', function () {
    /** @var \Tests\TestCase $this */
    $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'soci'");
    $columnNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

    expect($columnNames)
        ->toContain('codice_fiscale')
        ->toContain('matricola')
        ->toContain('nome')
        ->toContain('cognome')
        ->toContain('stato_iscrizione'); // MySQL specific
});

test('documenti table schema', function () {
    /** @var \Tests\TestCase $this */
    $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'documenti'");
    $columnNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

    expect($columnNames)
        ->toContain('id_univoco')
        ->toContain('nome_file')
        ->toContain('socio_cf')  // MySQL specific
        ->toContain('hash_file') // MySQL specific
        ->toContain('data_caricamento');
});
