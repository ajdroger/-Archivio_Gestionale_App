<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\Enum\StatoIscrizione;

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    $db = DatabaseConnection::getConnection();
    $uploadDir = __DIR__ . '/../../storage/uploads/';
    // Ensure storage path is correct per repository logic: __DIR__ . '/../../../storage/uploads/'
    // From bin/tools/ it is ../../storage/uploads/

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    echo "\n=== MASSIVE SEEDER v2.0 (200 UTENTI) ===\n";
    echo "Questo script cancellerà i soci esistenti e ne creerà 200 nuovi.\n";
    echo "Attendi...\n";

    // 1. CLEANUP (Preserve logins if needed, but Soci table is separate from Users table generally unless linked)
    // Soci table: codice_fiscale PK. Users table: login credentials.
    // The previous scripts imply loose coupling or no coupling for "Simulazione".
    // I will clean `documenti` and `soci`.
    $db->exec("DELETE FROM documenti");
    $db->exec("DELETE FROM soci");
    echo "[OK] Tabelle 'soci' e 'documenti' svuotate.\n";

    // 2. DATA GENERATORS
    $nomi = ['Marco', 'Giuseppe', 'Antonio', 'Giovanni', 'Roberto', 'Luigi', 'Francesco', 'Mario', 'Paolo', 'Michele', 'Anna', 'Maria', 'Rosa', 'Giovanna', 'Giulia', 'Lucia', 'Francesca', 'Elena', 'Laura', 'Rita'];
    $cognomi = ['Rossi', 'Bianchi', 'Esposito', 'Ricci', 'Romano', 'Colombo', 'Ferrari', 'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Mancini', 'Costa', 'Giordano', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri'];
    $vie = ['Via Roma', 'Via Garibaldi', 'Corso Italia', 'Via Dante', 'Via Mazzini', 'Via Verdi', 'Piazza Duomo', 'Viale Kennedy', 'Via dei Mille'];

    $stmtSocio = $db->prepare("INSERT INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, telefono, stato_iscrizione) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtDoc = $db->prepare("INSERT INTO documenti (id_univoco, nome_file, hash_file, stato, data_caricamento, tipo_documento, socio_cf, anno_solare, quota_versata, metodo_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $templatePdfPath = __DIR__ . '/../../Documentazione/Presentazioni/presentazione.pdf';
    $hasTemplate = file_exists($templatePdfPath);

    // 3. GENERATION LOOP
    $year = date('Y');
    for ($i = 0; $i < 200; $i++) {
        $nome = $nomi[array_rand($nomi)];
        $cognome = $cognomi[array_rand($cognomi)];

        // Generate CF (Fake but plausible format)
        $cf = strtoupper(substr($cognome, 0, 3) . substr($nome, 0, 3)) . rand(10, 99) . "A" . rand(10, 70) . "H501" . chr(rand(65, 90));
        // Ensure standard length 16 just in case
        $cf = substr($cf . "XXXXXX", 0, 16);

        $matricola = date('Y') . '/SIM/' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        $dob = (1950 + rand(0, 55)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $indirizzo = $vie[array_rand($vie)] . ' ' . rand(1, 150) . ', Firenze';
        $email = strtolower($nome . '.' . $cognome . '.' . rand(1, 999) . '@email.sim');
        $telefono = '+39 3' . rand(0, 9) . rand(0, 9) . ' ' . rand(1000000, 9999999);

        // Distribution: 80% Active, 10% Suspended, 10% Resigned
        $randState = rand(1, 100);
        if ($randState <= 80)
            $stato = 'ATTIVO';
        elseif ($randState <= 90)
            $stato = 'SOSPESO';
        else
            $stato = 'DIMISSIONARIO';

        // Insert Socio
        try {
            $stmtSocio->execute([$cf, $matricola, $nome, $cognome, $dob, $indirizzo, $email, $telefono, $stato]);
        } catch (PDOException $e) {
            // Handle duplicates if lucky random generates same CF
            if ($e->getCode() == '23000') { // Duplicate entry
                $i--; // Retry
                continue;
            }
            throw $e;
        }

        // Generate Documents (Modulo Iscrizione) for Active/Suspended
        if ($stato !== 'CANCELLATO') {
            $docId = uniqid('doc_');
            $fileName = "ModuloIscrizione_{$year}_{$cf}.pdf";
            $targetFile = $uploadDir . $docId . '_' . $fileName;

            // Copy template or create dummy
            if ($hasTemplate) {
                copy($templatePdfPath, $targetFile);
            } else {
                file_put_contents($targetFile, "%PDF-1.4 TEST PDF DOCUMENT CONTENT FOR: $nome $cognome");
            }
            $hash = hash_file('sha256', $targetFile);

            $docStato = ($stato === 'ATTIVO') ? 'VALIDATO' : 'IN_ATTESA';
            $quota = 50.00;

            $stmtDoc->execute([$docId, $fileName, $hash, $docStato, date('Y-m-d H:i:s'), 'MODULO_ISCRIZIONE', $cf, date('Y'), $quota, 'BONIFICO']);
        }

        if ($i % 20 === 0)
            echo ".";
    }

    echo "\n[OK] Generati 200 Soci e relativi documenti.\n";
    echo "=== COMPLETATO ===\n";

} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
