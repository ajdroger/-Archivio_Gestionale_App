<?php

use MCAG\SecurityLayer\AuditTrail;
use MCAG\SecurityLayer\Operatore;

test('cf anonymization', function () {
    $audit = AuditTrail::getInstance();
    $user = new Operatore();
    $user->ID = 99;
    $user->Username = "test_user";

    // CF reale vs Mascherato
    $realCF = "RSSMRA80A01H501W";
    $maskedCF = "RSSM********501W";

    $audit->logEvento($user, "VIEW_SOCI", $realCF);

    $result = $audit->ricercaAzioni(['action' => 'VIEW_SOCI']);
    $events = $result['data'];
    $lastEvent = end($events);

    expect($lastEvent['resource_id'])->toBe($maskedCF);
});

test('username anonymization', function () {
    $audit = AuditTrail::getInstance();
    $user = new Operatore();
    $user->ID = 100;
    $user->Username = "amministratore_molto_lungo";

    $audit->logEvento($user, "TEST_ANON", "res");

    // Pass -1 for unlimited to match old behavior or just default
    $result = $audit->ricercaAzioni(['action' => 'TEST_ANON']);
    $events = $result['data'];
    $lastEvent = end($events);

    expect($lastEvent['username'])->toContain("...");
});
