<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require_once __DIR__ . '/../../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    $db = DatabaseConnection::getConnection();

    echo "=== POPOLAMENTO SOCI DI TEST ===\n";

    // PULIZIA TABELLE
    $db->exec("DELETE FROM documenti");
    $db->exec("DELETE FROM soci");
    echo "[!] Tabelle pulite.\n";

    // 1. Giulia Verdi - Attiva
    $stmt = $db->prepare("INSERT OR REPLACE INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['VRDGLI90A01H501X', 'M002', 'GIULIA', 'VERDI', '1990-01-01', 'Via Roma 1, Firenze', 'giulia.verdi@email.com', 'ATTIVO']);
    echo "[+] Aggiunta Socio: Giulia Verdi (Attiva)\n";

    // Aggiungi un documento valido a Giulia per non farla risultare morosa
    $stmtDoc = $db->prepare("INSERT OR REPLACE INTO documenti (id_univoco, nome_file, hash_sha256, stato, data_caricamento, tipo_documento, codice_fiscale_socio, anno_solare, quota_versata, metodo_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtDoc->execute([uniqid(), 'iscrizione_2025_giulia.pdf', hash('sha256', 'giulia'), 'VALIDATO', date('Y-m-d'), 'MODULO_ISCRIZIONE', 'VRDGLI90A01H501X', 2025, 50.00, 'BONIFICO']);
    echo "    -> Documento Iscrizione 2025 aggiunto (Morosità: NO)\n";


    // 2. Luca Bianchi - Moroso (Nessun documento 2025)
    $stmt->execute(['BNCLCU85M22H501Z', 'M003', 'LUCA', 'BIANCHI', '1985-05-22', 'Via Milano 2, Firenze', 'luca.bianchi@email.com', 'ATTIVO']);
    echo "[+] Aggiunto Socio: Luca Bianchi (Attivo ma Moroso)\n";
    // Nota: Nessun documento aggiunto per il 2025 -> Dovrebbe risultare MOROSO


    // 3. Sofia Neri - Sospesa
    $stmt->execute(['NRISFO95T55H501K', 'M004', 'SOFIA', 'NERI', '1995-12-15', 'Piazza Duomo 3, Firenze', 'sofia.neri@email.com', 'SOSPESO']);
    echo "[+] Aggiunto Socio: Sofia Neri (Sospesa)\n";

    echo "=== OPERAZIONE COMPLETATA ===\n";

} catch (Exception $e) {
    echo "[ERRORE] " . $e->getMessage() . "\n";
}

