<?php

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;

describe('GDPR Compliance Tests', function () {

    beforeEach(function () {
        $pdo = DatabaseConnection::getConnection();
        $pdo->exec("DELETE FROM soci WHERE codice_fiscale IN ('TSTDLT85M01H501Z', 'TSTEXP85M01H501Z', 'TSTAUD85M01H501Z')");
    });

    test('hardDelete removes all personal data permanently', function () {
        $pdo = DatabaseConnection::getConnection();
        $repo = new PDOSocioRepository($pdo);

        $testCF = 'TSTDLT85M01H501Z';
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $statusCol = ($driver === 'mysql') ? 'stato_iscrizione' : 'stato';

        $pdo->exec("INSERT INTO soci (codice_fiscale, nome, cognome, data_nascita, matricola, $statusCol) 
                          VALUES ('$testCF', 'Test', 'Delete', '1985-08-13', 'TEST001', 'ATTIVO')");

        $socio = $repo->findByCodiceFiscale($testCF);
        expect($socio)->not->toBeNull();

        $result = $repo->hardDelete($testCF);
        expect($result)->toBeTrue();

        $deleted = $repo->findByCodiceFiscale($testCF);
        expect($deleted)->toBeNull();
    })->group('gdpr', 'critical');

    test('exportGDPRData returns complete personal data export', function () {
        $pdo = DatabaseConnection::getConnection();
        $repo = new PDOSocioRepository($pdo);

        // Create test data
        $testCF = 'TSTEXP85M01H501Z';
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $statusCol = ($driver === 'mysql') ? 'stato_iscrizione' : 'stato';

        // Cleanup any existing test data
        $pdo->exec("DELETE FROM soci WHERE codice_fiscale = '$testCF'");

        $pdo->exec("INSERT INTO soci (codice_fiscale, nome, cognome, data_nascita, matricola, $statusCol) 
                          VALUES ('$testCF', 'Test', 'Export', '1985-08-13', 'TEST003', 'ATTIVO')");

        $export = $repo->exportGDPRData($testCF);

        expect($export)->toHaveKeys(['export_date', 'data_subject', 'membership_data', 'documents', 'consents']);
        expect($export['data_subject'])->toHaveKeys(['codice_fiscale', 'nome', 'cognome']);
        expect($export['data_subject']['codice_fiscale'])->toBe($testCF);
        expect($export['membership_data'])->toHaveKey('matricola');
        expect($export['export_date'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');

        // Cleanup
        $pdo->exec("DELETE FROM soci WHERE codice_fiscale = '$testCF'");
    })->group('gdpr', 'critical');

    test('exportGDPRData handles non-existent socio gracefully', function () {
        $repo = new PDOSocioRepository(DatabaseConnection::getConnection());
        $export = $repo->exportGDPRData('NONEXISTENT123');
        expect($export)->toBeEmpty();
    })->group('gdpr');

    test('hardDelete creates audit log entry', function () {
        $pdo = DatabaseConnection::getConnection();
        $repo = new PDOSocioRepository($pdo);

        $testCF = 'TSTAUD85M01H501Z';
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $statusCol = ($driver === 'mysql') ? 'stato_iscrizione' : 'stato';

        $pdo->exec("INSERT INTO soci (codice_fiscale, nome, cognome, data_nascita, matricola, $statusCol) 
                          VALUES ('$testCF', 'Test', 'Audit', '1985-08-13', 'TEST002', 'ATTIVO')");

        $repo->hardDelete($testCF);

        $stmt = $pdo->query("SELECT * FROM audit_logs WHERE action = 'GDPR_HARD_DELETE' ORDER BY id DESC LIMIT 1");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        expect($log)->not->toBeFalse();
        expect($log['resource_id'])->toContain('***');
    })->group('gdpr', 'audit');
});
