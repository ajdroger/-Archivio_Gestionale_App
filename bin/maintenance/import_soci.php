<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

// Configurazione
$inputFile = __DIR__ . '/../../public/data/nuovi_soci.json';

if (!file_exists($inputFile)) {
    echo "[ERRORE] File '$inputFile' non trovato.\n";
    echo "Crea un file 'nuovi_soci.json' in public/data/ copiando il template.\n";
    exit(1);
}

$jsonData = file_get_contents($inputFile);
$soci = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "[ERRORE] JSON non valido: " . json_last_error_msg() . "\n";
    exit(1);
}

try {
    $db = DatabaseConnection::getConnection();
    echo "=== IMPORTAZIONE SOCI DA FILE JSON (MySQL) ===\n";
    echo "Trovati " . count($soci) . " soci da importare.\n";

    // MySQL Query: ON DUPLICATE KEY UPDATE
    $sqlSoci = "INSERT INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, stato_iscrizione) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                matricola=VALUES(matricola), nome=VALUES(nome), cognome=VALUES(cognome), 
                data_nascita=VALUES(data_nascita), indirizzo=VALUES(indirizzo), 
                email=VALUES(email), stato_iscrizione=VALUES(stato_iscrizione)";

    $sqlDoc = "INSERT INTO documenti (id_univoco, nome_file, hash_file, stato, data_caricamento, tipo_documento, socio_cf, anno_solare, quota_versata, metodo_pagamento) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE
               quota_versata=VALUES(quota_versata), stato=VALUES(stato)";

    $stmtSocio = $db->prepare($sqlSoci);
    $stmtDoc = $db->prepare($sqlDoc);

    $count = 0;
    foreach ($soci as $socio) {
        // Validazione base
        if (empty($socio['codice_fiscale']) || empty($socio['cognome'])) {
            echo "[SKIP] Dati mancanti per un socio.\n";
            continue;
        }

        // Inserimento Socio
        $stmtSocio->execute([
            $socio['codice_fiscale'],
            $socio['matricola'] ?? 'TEMP' . rand(1000, 9999),
            $socio['nome'],
            $socio['cognome'],
            $socio['data_nascita'] ?? '1900-01-01',
            $socio['indirizzo'] ?? '',
            $socio['email'] ?? '',
            $socio['stato'] ?? 'ATTIVO'
        ]);

        echo "[OK] Importato: {$socio['nome']} {$socio['cognome']} ({$socio['codice_fiscale']})\n";

        // Inserimento Documento Iscrizione (Opzionale)
        if (isset($socio['documento_anno']) && isset($socio['quota_versata'])) {
            $stmtDoc->execute([
                uniqid(),
                'import_auto_' . $socio['documento_anno'] . '.pdf',
                hash('sha256', $socio['codice_fiscale'] . $socio['documento_anno']),
                'VALIDATO',
                date('Y-m-d H:i:s'),
                'MODULO_ISCRIZIONE',
                $socio['codice_fiscale'],
                (int) $socio['documento_anno'],
                (float) $socio['quota_versata'],
                $socio['metodo_pagamento'] ?? 'CONTANTI'
            ]);
            echo "    -> Iscrizione {$socio['documento_anno']} registrata.\n";
        } else {
            echo "    -> Nessun documento iscrizione (Socio MOROSO).\n";
        }

        $count++;
    }

    echo "=== COMPLETATO: $count soci importati ===\n";

} catch (Exception $e) {
    echo "[ERRORE CRITICO] " . $e->getMessage() . "\n";
    exit(1);
}

