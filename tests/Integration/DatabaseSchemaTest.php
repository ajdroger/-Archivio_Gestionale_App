<?php

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->db = DatabaseConnection::getConnection();
});

test('required tables exist', function () {
    /** @var \Tests\TestCase $this */
    $requiredTables = ['soci', 'documenti'];

    $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $stmt = $this->db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
    } else {
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table'");
    }

    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($requiredTables as $table) {
        expect($existingTables)->toContain($table);
    }
});

test('socio table schema', function () {
    /** @var \Tests\TestCase $this */
    $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'soci'");
        $columnNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $this->db->query("PRAGMA table_info(soci)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
    }

    expect($columnNames)->toContain('codice_fiscale')
        ->toContain('matricola')
        ->toContain('nome')
        ->toContain('cognome');
    // Note: 'stato' renamed to 'stato_iscrizione' in MySQL
});

test('documenti table schema', function () {
    /** @var \Tests\TestCase $this */
    $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'documenti'");
        $columnNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $this->db->query("PRAGMA table_info(documenti)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
    }

    expect($columnNames)->toContain('id_univoco')
        ->toContain('nome_file');
    // Note: 'codice_fiscale_socio' → 'socio_cf', 'hash_sha256' → 'hash_file' in MySQL
});
