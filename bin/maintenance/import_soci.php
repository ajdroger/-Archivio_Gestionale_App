<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

// Configurazione
$inputFile = __DIR__ . '/../public/data/nuovi_soci.json';

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
    echo "=== IMPORTAZIONE SOCI DA FILE JSON ===\n";
    echo "Trovati " . count($soci) . " soci da importare.\n";

    $stmtSocio = $db->prepare("INSERT OR REPLACE INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtDoc = $db->prepare("INSERT OR REPLACE INTO documenti (id_univoco, nome_file, hash_sha256, stato, data_caricamento, tipo_documento, codice_fiscale_socio, anno_solare, quota_versata, metodo_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
            $socio['matricola'] ?? 'TEMP' . rand(1000, 9999), // Matricola provvisoria se mancante
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
                'VALIDATO', // Assumiamo validato se presente in import massivo
                date('Y-m-d'),
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
