<?php

namespace FratellanzaMilitare\InfrastrutturaIT\Persistence;

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\SocioRepository;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDODocumentoRepository;
use PDO;
use DateTime;

class PDOSocioRepository implements SocioRepository
{
    private PDO $pdo;
    private PDODocumentoRepository $docRepo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseConnection::getConnection();
        $this->docRepo = new PDODocumentoRepository($this->pdo);
    }

    public function save(Socio $socio): void
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            // MySQL Logic (Strict)
            $sql = "INSERT INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, telefono, stato_iscrizione) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    matricola=VALUES(matricola), nome=VALUES(nome), cognome=VALUES(cognome), 
                    data_nascita=VALUES(data_nascita), indirizzo=VALUES(indirizzo), email=VALUES(email), 
                    telefono=VALUES(telefono), stato_iscrizione=VALUES(stato_iscrizione)";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                $socio->CodiceFiscale,
                $socio->Matricola,
                $socio->DatiPersonali->Nome,
                $socio->DatiPersonali->Cognome,
                $socio->DatiPersonali->DataNascita->format('Y-m-d'),
                $socio->DatiPersonali->Indirizzo,
                $socio->DatiPersonali->Email,
                $socio->DatiPersonali->Telefono,
                $socio->Stato->name
            ]);

            // Sync Associated Documents
            $existingDocs = $this->docRepo->findBySocio($socio->CodiceFiscale);
            $existingIds = array_map(fn($d) => $d->IdUnivoco, $existingDocs);
            $currentIds = array_map(fn($d) => $d->IdUnivoco, $socio->DocumentiAssociati);

            $idsToDelete = array_diff($existingIds, $currentIds);
            foreach ($idsToDelete as $id) {
                $this->docRepo->delete($id);
            }

            foreach ($socio->DocumentiAssociati as $doc) {
                $this->docRepo->save($doc, $socio->CodiceFiscale);
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findByCodiceFiscale(string $cf): ?Socio
    {
        $stmt = $this->pdo->prepare("SELECT * FROM soci WHERE codice_fiscale = ?");
        $stmt->execute([$cf]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->mapRowToSocio($data);
    }

    public function findAll(): array
    {
        $anno = (int) date('Y');

        // MySQL uses 'socio_cf' and standard SQL
        $sql = "SELECT s.*, 
                (SELECT 1 FROM documenti d 
                 WHERE d.socio_cf = s.codice_fiscale 
                 AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                 AND d.anno_solare = ? 
                 AND d.stato = 'VALIDATO' 
                 LIMIT 1) as is_pagato
                FROM soci s";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anno]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soci = [];
        foreach ($rows as $row) {
            $soci[] = $this->mapRowToSocio($row, false);
        }
        return $soci;
    }

    public function delete(string $cf): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM soci WHERE codice_fiscale = ?");
        $stmt->execute([$cf]);
    }

    private function mapRowToSocio(array $row, bool $hydrateDocuments = true): Socio
    {
        $socio = new Socio();
        $socio->CodiceFiscale = $row['codice_fiscale'] ?? 'UNKNOWN';
        $socio->Matricola = $row['matricola'] ?? '-';

        try {
            // MySQL standard column
            $statusName = $row['stato_iscrizione'] ?? 'IN_ATTESA';
            $socio->Stato = constant("FratellanzaMilitare\\Enum\\StatoIscrizione::{$statusName}");
        } catch (\Throwable $e) {
            $socio->Stato = StatoIscrizione::IN_ATTESA;
        }

        $dati = new DatiAnagrafici();
        $dati->Nome = $row['nome'] ?? '';
        $dati->Cognome = $row['cognome'] ?? '';

        try {
            $dateStr = $row['data_nascita'] ?? '1900-01-01';
            $dati->DataNascita = new DateTime($dateStr);
        } catch (\Exception $e) {
            $dati->DataNascita = new DateTime('1900-01-01');
        }

        $dati->Indirizzo = $row['indirizzo'] ?? '';
        $dati->Email = $row['email'] ?? '';
        $dati->Telefono = $row['telefono'] ?? '';

        $socio->DatiPersonali = $dati;

        if (array_key_exists('is_pagato', $row)) {
            $socio->IsMorosoPrecalculated = !((bool) $row['is_pagato']);
        }

        if ($hydrateDocuments) {
            $socio->DocumentiAssociati = $this->docRepo->findBySocio($socio->CodiceFiscale);
        }

        return $socio;
    }

    public function getStatistics(): array
    {
        $annoCorrente = (int) date('Y');

        // Total Soci
        $total = $this->pdo->query("SELECT COUNT(*) FROM soci")->fetchColumn();

        // Active Soci
        $stmtActive = $this->pdo->prepare("SELECT COUNT(*) FROM soci WHERE stato_iscrizione = ?");
        $stmtActive->execute([StatoIscrizione::ATTIVO->name]);
        $attivi = $stmtActive->fetchColumn();

        // Paganti
        $sqlPaganti = "SELECT COUNT(*) FROM soci s 
                       WHERE EXISTS (
                           SELECT 1 FROM documenti d 
                           WHERE d.socio_cf = s.codice_fiscale 
                           AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                           AND d.anno_solare = ? 
                           AND d.stato = 'VALIDATO'
                       )";
        $stmtPaganti = $this->pdo->prepare($sqlPaganti);
        $stmtPaganti->execute([$annoCorrente]);
        $paganti = $stmtPaganti->fetchColumn();

        $morosi = $total - $paganti;

        // 1. Financial Trend (MySQL MONTH())
        $sqlTrend = "SELECT MONTH(data_caricamento) as mese, COUNT(*) as count 
                     FROM documenti 
                     WHERE tipo_documento = 'MODULO_ISCRIZIONE' 
                     AND stato = 'VALIDATO' 
                     AND anno_solare = ?
                     GROUP BY mese 
                     ORDER BY mese";
        $stmtTrend = $this->pdo->prepare($sqlTrend);
        $stmtTrend->execute([$annoCorrente]);
        $rowsTrend = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

        $trend = array_fill(1, 12, 0);
        foreach ($rowsTrend as $row) {
            $trend[(int) $row['mese']] = (int) $row['count'];
        }

        // 2. Demographics (MySQL TIMESTAMPDIFF)
        $ageExpr = "TIMESTAMPDIFF(YEAR, data_nascita, CURDATE())";

        $sqlAge = "SELECT 
                    CASE 
                        WHEN age < 18 THEN 'Under 18'
                        WHEN age BETWEEN 18 AND 30 THEN '18-30'
                        WHEN age BETWEEN 31 AND 50 THEN '31-50'
                        WHEN age BETWEEN 51 AND 70 THEN '51-70'
                        ELSE 'Over 70'
                    END as range_label,
                    COUNT(*) as count
                   FROM (
                       SELECT $ageExpr as age
                       FROM soci
                       WHERE stato_iscrizione = 'ATTIVO'
                   ) as sub
                   GROUP BY range_label";

        $stmtAge = $this->pdo->query($sqlAge);
        $rowsAge = $stmtAge->fetchAll(PDO::FETCH_KEY_PAIR);

        $demografica = array_merge([
            'Under 18' => 0,
            '18-30' => 0,
            '31-50' => 0,
            '51-70' => 0,
            'Over 70' => 0
        ], $rowsAge);

        return [
            'totale' => (int) $total,
            'attivi' => (int) $attivi,
            'morosi' => (int) $morosi,
            'paganti' => (int) $paganti,
            'perc_attivi' => $total > 0 ? round(($attivi / $total) * 100, 1) : 0,
            'perc_paganti' => $total > 0 ? round(($paganti / $total) * 100, 1) : 0,
            'perc_morosi' => $total > 0 ? round(($morosi / $total) * 100, 1) : 0,
            'trend_iscritti' => array_values($trend),
            'demografica' => $demografica,
            'data_riferimento' => date('d/m/Y H:i')
        ];
    }

    public function search(string $query): array
    {
        $term = "%$query%";
        $anno = (int) date('Y');

        // MySQL CONCAT
        $concat = "CONCAT(s.nome, ' ', s.cognome)";
        $concatRev = "CONCAT(s.cognome, ' ', s.nome)";

        $sql = "SELECT s.*, 
                (SELECT 1 FROM documenti d 
                 WHERE d.socio_cf = s.codice_fiscale 
                 AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                 AND d.anno_solare = ? 
                 AND d.stato = 'VALIDATO' 
                 LIMIT 1) as is_pagato
                FROM soci s 
                WHERE s.nome LIKE ? 
                   OR s.cognome LIKE ? 
                   OR s.codice_fiscale LIKE ? 
                   OR s.matricola LIKE ? 
                   OR s.email LIKE ? 
                   OR s.telefono LIKE ? 
                   OR $concat LIKE ? 
                   OR $concatRev LIKE ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anno, $term, $term, $term, $term, $term, $term, $term, $term]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soci = [];
        foreach ($rows as $row) {
            $soci[] = $this->mapRowToSocio($row, false);
        }
        return $soci;
    }

    public function findByFilters(array $filters): array
    {
        $anno = (int) date('Y');

        $sql = "SELECT s.*, 
                (SELECT 1 FROM documenti d 
                 WHERE d.socio_cf = s.codice_fiscale 
                 AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                 AND d.anno_solare = ? 
                 AND d.stato = 'VALIDATO' 
                 LIMIT 1) as is_pagato
                FROM soci s WHERE 1=1";

        $params = [$anno];

        if (!empty($filters['stato'])) {
            $sql .= " AND s.stato_iscrizione = ?";
            $params[] = $filters['stato'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soci = [];
        foreach ($rows as $row) {
            $socio = $this->mapRowToSocio($row, false);

            if (isset($filters['moroso'])) {
                $isMoroso = $socio->verificaMorosita();
                if ($filters['moroso'] === true && !$isMoroso) {
                    continue;
                }
                if ($filters['moroso'] === false && $isMoroso) {
                    continue;
                }
            }

            $soci[] = $socio;
        }
        return $soci;
    }

    public function hardDelete(string $codiceFiscale): bool
    {
        try {
            $this->pdo->beginTransaction();

            $stmtDocs = $this->pdo->prepare("SELECT id_univoco, nome_file FROM documenti WHERE socio_cf = ?");
            $stmtDocs->execute([$codiceFiscale]);
            $docs = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

            foreach ($docs as $doc) {
                // Percorso convenzionale dei file
                $filePath = __DIR__ . '/../../../storage/uploads/' . $doc['id_univoco'] . '_' . $doc['nome_file'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $stmtDelDocs = $this->pdo->prepare("DELETE FROM documenti WHERE socio_cf = ?");
            $stmtDelDocs->execute([$codiceFiscale]);

            $stmtDelSocio = $this->pdo->prepare("DELETE FROM soci WHERE codice_fiscale = ?");
            $stmtDelSocio->execute([$codiceFiscale]);

            $this->pdo->commit();

            $pseudoCF = substr($codiceFiscale, 0, 3) . '***' . substr($codiceFiscale, -3);
            $trail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
            $trail->logEvento(
                null,
                'GDPR_HARD_DELETE',
                $pseudoCF
            );

            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function exportGDPRData(string $codiceFiscale): array
    {
        $socio = $this->findByCodiceFiscale($codiceFiscale);
        if (!$socio) {
            return [];
        }

        // In questa versione dello schema, data_iscrizione e data_scadenza sono gestite via documenti
        $dbData = ['data_iscrizione' => null, 'data_scadenza' => null];

        $docRepo = new PDODocumentoRepository($this->pdo);

        try {
            $documents = $docRepo->findBySocio($codiceFiscale);
        } catch (\Exception $e) {
            $documents = [];
        }

        return [
            'export_date' => date('Y-m-d H:i:s'),
            'data_subject' => [
                'codice_fiscale' => $socio->CodiceFiscale,
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'data_nascita' => $socio->DatiPersonali->DataNascita?->format('Y-m-d'),
                'luogo_nascita' => $socio->DatiPersonali->LuogoNascita ?? null,
                'sesso' => $socio->DatiPersonali->Sesso ?? null,
                'indirizzo' => $socio->DatiPersonali->Indirizzo ?? null,
                'email' => $socio->DatiPersonali->Email ?? null,
                'telefono' => $socio->DatiPersonali->Telefono ?? null,
            ],
            'membership_data' => [
                'matricola' => $socio->Matricola,
                'stato' => $socio->Stato->value ?? $socio->Stato,
                'data_iscrizione' => $dbData['data_iscrizione'] ?? null,
                'data_scadenza' => $dbData['data_scadenza'] ?? null,
            ],
            'documents' => array_map(function ($doc) {
                return [
                    'tipo' => $doc->TipoDocumento,
                    'nome_file' => $doc->NomeFile,
                    'data_caricamento' => $doc->DataCaricamento->format('Y-m-d H:i:s'),
                    'stato' => $doc->Stato->value ?? $doc->Stato,
                    'anno_solare' => $doc->AnnoSolare ?? null,
                    'quota_versata' => $doc->QuotaVersata ?? null,
                ];
            }, $documents),
            'consents' => array_map(function ($doc) {
                if ($doc->ConsensoGDPR) {
                    return [
                        'trattamento_dati' => $doc->ConsensoGDPR->TrattamentoDati,
                        'cessione_terzi' => $doc->ConsensoGDPR->CessioneTerzi,
                        'marketing' => $doc->ConsensoGDPR->Marketing,
                        'data_firma' => $doc->ConsensoGDPR->DataFirma?->format('Y-m-d H:i:s'),
                    ];
                }
                return null;
            }, array_filter($documents, fn($doc) => $doc->ConsensoGDPR !== null)),
        ];
    }
}
