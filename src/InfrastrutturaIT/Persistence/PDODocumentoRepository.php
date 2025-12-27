<?php

namespace FratellanzaMilitare\InfrastrutturaIT\Persistence;

use FratellanzaMilitare\GestioneSoci\Documento;
use FratellanzaMilitare\GestioneSoci\ModuloIscrizione;
use FratellanzaMilitare\GestioneSoci\ConsensoGDPR;
use FratellanzaMilitare\GestioneSoci\DocumentoRepository;
use FratellanzaMilitare\Enum\StatoDocumento;
use PDO;
use DateTime;

class PDODocumentoRepository implements DocumentoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseConnection::getConnection();
    }

    public function save(Documento $documento, string $socioCf): void
    {
        $tipo = 'GENERICO';
        $anno = null;
        $quota = null;
        $metodo = null;
        $trattamento = null;
        $cessione = null;
        $marketing = null;
        $firma = null;

        if ($documento instanceof ModuloIscrizione) {
            $tipo = 'MODULO_ISCRIZIONE';
            $anno = $documento->AnnoSolare;
            $quota = $documento->QuotaVersata;
            $metodo = $documento->MetodoPagamento;
        } elseif ($documento instanceof ConsensoGDPR) {
            $tipo = 'CONSENSO_GDPR';
            $trattamento = $documento->TrattamentoDati ? 1 : 0;
            $cessione = $documento->CessioneTerzi ? 1 : 0;
            $marketing = $documento->Marketing ? 1 : 0;
            $firma = $documento->DataFirma->format('Y-m-d H:i:s');
        }

        // MySQL Logic
        $sql = "INSERT INTO documenti (
            id_univoco, nome_file, hash_file, stato, data_caricamento, tipo_documento, socio_cf,
            anno_solare, quota_versata, metodo_pagamento,
            trattamento_dati, cessione_terzi, marketing, data_firma
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            nome_file=VALUES(nome_file), hash_file=VALUES(hash_file), stato=VALUES(stato), 
            data_caricamento=VALUES(data_caricamento), tipo_documento=VALUES(tipo_documento), socio_cf=VALUES(socio_cf),
            anno_solare=VALUES(anno_solare), quota_versata=VALUES(quota_versata), metodo_pagamento=VALUES(metodo_pagamento),
            trattamento_dati=VALUES(trattamento_dati), cessione_terzi=VALUES(cessione_terzi), marketing=VALUES(marketing), data_firma=VALUES(data_firma)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $documento->IdUnivoco,
            $documento->NomeFile,
            $documento->HashSHA256,
            $documento->Stato->name,
            $documento->DataCaricamento->format('Y-m-d H:i:s'),
            $tipo,
            $socioCf,
            $anno,
            $quota,
            $metodo,
            $trattamento,
            $cessione,
            $marketing,
            $firma
        ]);
    }

    public function findById(string $uuid): ?Documento
    {
        return null;
    }

    public function findBySocio(string $socioCf): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documenti WHERE socio_cf = ?");
        $stmt->execute([$socioCf]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $docs = [];
        foreach ($rows as $row) {
            $docs[] = $this->mapRowToDocumento($row);
        }
        return $docs;
    }

    /**
     * Batch loading for N+1 query optimization
     * 
     * Loads documents for multiple soci in a single query instead of N queries.
     * Result: 101 queries → 2 queries for 100 soci
     * 
     * @param array<string> $codiciFiscali Array of codici fiscali
     * @return array<string, array<Documento>> Map of CF => documents array
     */
    public function findBySocioBatch(array $codiciFiscali): array
    {
        if (empty($codiciFiscali)) {
            return [];
        }

        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($codiciFiscali), '?'));

        $sql = "SELECT * FROM documenti WHERE socio_cf IN ($placeholders) ORDER BY socio_cf, data_caricamento DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($codiciFiscali);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group documents by socio
        $grouped = [];
        foreach ($codiciFiscali as $cf) {
            $grouped[$cf] = []; // Initialize empty arrays for all CFs
        }

        foreach ($rows as $row) {
            $cf = $row['socio_cf'];
            $grouped[$cf][] = $this->mapRowToDocumento($row);
        }

        return $grouped;
    }

    public function delete(string $idUnivoco): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM documenti WHERE id_univoco = ?");
        $stmt->execute([$idUnivoco]);
    }

    private function mapRowToDocumento(array $row): Documento
    {
        $doc = null;
        if ($row['tipo_documento'] === 'MODULO_ISCRIZIONE') {
            $doc = new ModuloIscrizione();
            $doc->AnnoSolare = (int) ($row['anno_solare'] ?? 0);
            $doc->QuotaVersata = (float) ($row['quota_versata'] ?? 0.0);
            $doc->MetodoPagamento = $row['metodo_pagamento'] ?? ''; // Handle NULL gracefully
        } elseif ($row['tipo_documento'] === 'CONSENSO_GDPR') {
            $doc = new ConsensoGDPR();
            $doc->TrattamentoDati = (bool) ($row['trattamento_dati'] ?? false);
            $doc->CessioneTerzi = (bool) ($row['cessione_terzi'] ?? false);
            $doc->Marketing = (bool) ($row['marketing'] ?? false);
            $doc->DataFirma = new DateTime($row['data_firma']);
        } else {
            $doc = new \FratellanzaMilitare\GestioneSoci\DocumentoGenerico();
        }

        $doc->IdUnivoco = $row['id_univoco'];
        $doc->NomeFile = $row['nome_file'];
        $doc->HashSHA256 = $row['hash_file'] ?? '';
        $doc->DataCaricamento = new DateTime($row['data_caricamento']);

        // Handle Stato (enum) with fallback for invalid values
        try {
            $doc->Stato = constant("FratellanzaMilitare\\Enum\\StatoDocumento::" . $row['stato']);
        } catch (\Error $e) {
            // Fallback to IN_ATTESA if stato is invalid or NULL
            $doc->Stato = StatoDocumento::IN_ATTESA;
        }

        return $doc;
    }
}
