<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    $db = DatabaseConnection::getConnection();
    $uploadDir = __DIR__ . '/../public/uploads/';
    $templatePdf = __DIR__ . '/../Documentazione/Presentazioni/presentazione.pdf';

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    echo "=== SIMULAZIONE DATI REALE (20 SOCI) ===\n";

    // PULIZIA DATI PRECEDENTI per pulizia assoluta
    $db->exec("DELETE FROM documenti");
    $db->exec("DELETE FROM soci");
    echo "[!] Tabelle pulite.\n";

    $sociData = [
        ['nome' => 'Marco', 'cognome' => 'Rossi', 'cf' => 'RSSMRC80A01H501U'],
        ['nome' => 'Giuseppe', 'cognome' => 'Esposito', 'cf' => 'SPSGPP75M12L219Y'],
        ['nome' => 'Anna', 'cognome' => 'Bianchi', 'cf' => 'BNCNNA85B42F205F'],
        ['nome' => 'Maria', 'cognome' => 'Romano', 'cf' => 'RMNMRA90E50H501O'],
        ['nome' => 'Antonio', 'cognome' => 'Colombo', 'cf' => 'CLMNTN68T15I625G'],
        ['nome' => 'Francesco', 'cognome' => 'Ricci', 'cf' => 'RCCFNC82S10D612P'],
        ['nome' => 'Giovanni', 'cognome' => 'Marino', 'cf' => 'MRNGNN77L05C352W'],
        ['nome' => 'Lucia', 'cognome' => 'Greco', 'cf' => 'GRCLCU88D44A944Z'],
        ['nome' => 'Roberto', 'cognome' => 'Bruno', 'cf' => 'BRNRRT72P01L682H'],
        ['nome' => 'Elena', 'cognome' => 'Gambino', 'cf' => 'GMBLNE92A55H501D'],
        ['nome' => 'Alessandro', 'cognome' => 'Ferrari', 'cf' => 'FRRLSN84M08E202U'],
        ['nome' => 'Silvia', 'cognome' => 'Fontana', 'cf' => 'FNTSLV79R60F205K'],
        ['nome' => 'Luca', 'cognome' => 'Moretti', 'cf' => 'MRTLTC86A22H501L'],
        ['nome' => 'Giulia', 'cognome' => 'Sartori', 'cf' => 'SRTGLI91H41A944T'],
        ['nome' => 'Stefano', 'cognome' => 'De Luca', 'cf' => 'DLCSFN73B15H501W'],
        ['nome' => 'Francesca', 'cognome' => 'Martini', 'cf' => 'MRTFNC81L52C352V'],
        ['nome' => 'Paolo', 'cognome' => 'Riva', 'cf' => 'RVIPLA83S18F205I'],
        ['nome' => 'Roberta', 'cognome' => 'Galli', 'cf' => 'GLLRRT76E45H501Q'],
        ['nome' => 'Fabio', 'cognome' => 'Conti', 'cf' => 'CNTFBA89H03A944R'],
        ['nome' => 'Laura', 'cognome' => 'Barbieri', 'cf' => 'BRBLRA80P41L219E']
    ];

    $stmtSocio = $db->prepare("INSERT INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, telefono, stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtDoc = $db->prepare("INSERT INTO documenti (id_univoco, nome_file, hash_sha256, stato, data_caricamento, tipo_documento, codice_fiscale_socio, anno_solare, quota_versata, metodo_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($sociData as $i => $s) {
        $matricola = 'M' . (100 + $i);
        $dataNascita = (1960 + rand(0, 40)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $indirizzo = "Via Firenze " . (1 + $i);
        $email = strtolower($s['nome'] . "." . $s['cognome'] . "@example.it");
        $telefono = "339" . rand(1000000, 9999999);
        $stato = (rand(0, 10) > 8) ? 'SOSPESO' : 'ATTIVO';

        $stmtSocio->execute([$s['cf'], $matricola, $s['nome'], $s['cognome'], $dataNascita, $indirizzo, $email, $telefono, $stato]);

        // Crea Modulo Iscrizione 2025 (per non essere moroso)
        $uniqueId = uniqid();
        $filename = "iscrizione_2025_" . $s['cf'] . ".pdf";
        $targetPath = $uploadDir . $uniqueId . '_' . $filename;

        if (file_exists($templatePdf)) {
            copy($templatePdf, $targetPath);
        } else {
            file_put_contents($targetPath, "%PDF-1.4 Fake PDF file for " . $s['nome']);
        }

        $stmtDoc->execute([
            $uniqueId,
            $filename,
            hash_file('sha256', $targetPath),
            'VALIDATO',
            date('Y-m-d H:i:s'),
            'MODULO_ISCRIZIONE',
            $s['cf'],
            2025,
            50.00,
            'SPORTELLO'
        ]);

        echo "[+] Socio aggiunto: " . $s['nome'] . " " . $s['cognome'] . " (" . $s['cf'] . ")\n";
    }

    echo "=== SIMULAZIONE COMPLETATA CON SUCCESSO ===\n";

} catch (Exception $e) {
    echo "[ERRORE] " . $e->getMessage() . "\n";
    print_r($e->getTraceAsString());
}
