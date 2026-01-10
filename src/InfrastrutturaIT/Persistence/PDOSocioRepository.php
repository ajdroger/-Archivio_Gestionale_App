<?php

namespace FratellanzaMilitare\InfrastrutturaIT\Persistence;

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\SocioRepository;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDODocumentoRepository;
use PDO;
use DateTime;

/**
 * Repository MySQL/MariaDB per la gestione dei Soci.
 * 
 * Implementa le operazioni CRUD complete e gestisce la persistenza del grafo
 * degli oggetti (Socio -> Documenti). Utilizza transazioni per garantire
 * l'integrità dei dati durante il salvataggio o l'aggiornamento.
 */
class PDOSocioRepository implements SocioRepository
{
    use SoftDeletable;

    private PDO $pdo;
    private PDODocumentoRepository $docRepo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseConnection::getConnection();
        $this->docRepo = new PDODocumentoRepository($this->pdo);
    }

    /**
     * Salva o aggiorna un socio e tutti i suoi documenti associati.
     * 
     * L'operazione è atomica (transazionale): se fallisce il salvataggio
     * di un documento, viene effettuato il rollback dell'intera operazione.
     * Sincronizza la collezione di documenti eliminando quelli rimossi.
     */
    public function save(Socio $socio): void
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $sql = "INSERT INTO soci (
                codice_fiscale, matricola, nome, cognome, data_nascita, 
                sesso, luogo_nascita, stato_civile,
                indirizzo, email, telefono, 
                grado, corpo_appartenenza, data_arruolamento, data_congedo,
                titolo_studio, professione,
                gruppo_sanguigno, note_mediche, contatto_emergenza,
                stato_iscrizione
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            matricola=VALUES(matricola), nome=VALUES(nome), cognome=VALUES(cognome), 
            data_nascita=VALUES(data_nascita), sesso=VALUES(sesso), luogo_nascita=VALUES(luogo_nascita), stato_civile=VALUES(stato_civile),
            indirizzo=VALUES(indirizzo), email=VALUES(email), telefono=VALUES(telefono), 
            grado=VALUES(grado), corpo_appartenenza=VALUES(corpo_appartenenza), data_arruolamento=VALUES(data_arruolamento), data_congedo=VALUES(data_congedo),
            titolo_studio=VALUES(titolo_studio), professione=VALUES(professione),
            gruppo_sanguigno=VALUES(gruppo_sanguigno), note_mediche=VALUES(note_mediche), contatto_emergenza=VALUES(contatto_emergenza),
            stato_iscrizione=VALUES(stato_iscrizione), deleted_at = NULL";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                $socio->CodiceFiscale,
                $socio->Matricola,
                $socio->DatiPersonali->Nome,
                $socio->DatiPersonali->Cognome,
                $socio->DatiPersonali->DataNascita->format('Y-m-d'),
                $socio->DatiPersonali->Sesso,
                $socio->DatiPersonali->LuogoNascita,
                $socio->DatiPersonali->StatoCivile,
                $socio->DatiPersonali->Indirizzo,
                $socio->DatiPersonali->Email,
                $socio->DatiPersonali->Telefono,
                $socio->Grado,
                $socio->CorpoAppartenenza,
                $socio->DataArruolamento?->format('Y-m-d'),
                $socio->DataCongedo?->format('Y-m-d'),
                $socio->DatiPersonali->TitoloStudio,
                $socio->DatiPersonali->Professione,
                $socio->GruppoSanguigno,
                $socio->NoteMediche,
                $socio->ContattoEmergenza,
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

    /**
     * Trova un socio per Codice Fiscale.
     * 
     * @return Socio|null L'entità trovata o null se inesistente.
     */
    public function findByCodiceFiscale(string $cf): ?Socio
    {
        $stmt = $this->pdo->prepare("SELECT * FROM soci WHERE codice_fiscale = ? AND deleted_at IS NULL");
        $stmt->execute([$cf]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->mapRowToSocio($data);
    }

    /**
     * Recupera tutti i soci registrati.
     * 
     * Include una subquery ottimizzata per pre-calcolare lo stato dei pagamenti
     * (is_pagato) ed evitare query N+1 successive.
     */
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
                FROM soci s
                WHERE s.deleted_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anno]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soci = [];
        foreach ($rows as $row) {
            $soci[] = $this->mapRowToSocio($row, false);
        }
        return $soci;
    }

    /**
     * Elimina un socio (Soft Delete).
     */
    public function delete(string $cf): void
    {
        $this->softDelete($cf);
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
        $dati->Sesso = $row['sesso'] ?? null;
        $dati->LuogoNascita = $row['luogo_nascita'] ?? null;
        $dati->StatoCivile = $row['stato_civile'] ?? null;
        $dati->TitoloStudio = $row['titolo_studio'] ?? null;
        $dati->Professione = $row['professione'] ?? null;

        $socio->DatiPersonali = $dati;

        // Mappatura Profilo Militare e Sanitario
        $socio->Grado = $row['grado'] ?? null;
        $socio->CorpoAppartenenza = $row['corpo_appartenenza'] ?? null;
        $socio->DataArruolamento = !empty($row['data_arruolamento']) ? new DateTime($row['data_arruolamento']) : null;
        $socio->DataCongedo = !empty($row['data_congedo']) ? new DateTime($row['data_congedo']) : null;

        $socio->GruppoSanguigno = $row['gruppo_sanguigno'] ?? null;
        $socio->NoteMediche = $row['note_mediche'] ?? null;
        $socio->ContattoEmergenza = $row['contatto_emergenza'] ?? null;

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

        // Total Soci (active + suspended + cancelled, excluding deleted)
        $total = $this->pdo->query("SELECT COUNT(*) FROM soci WHERE deleted_at IS NULL")->fetchColumn();

        // Active Soci
        $stmtActive = $this->pdo->prepare("SELECT COUNT(*) FROM soci WHERE stato_iscrizione = ? AND deleted_at IS NULL");
        $stmtActive->execute([StatoIscrizione::ATTIVO->name]);
        $attivi = $stmtActive->fetchColumn();

        // Paganti
        $sqlPaganti = "SELECT COUNT(*) FROM soci s 
                       WHERE s.deleted_at IS NULL AND EXISTS (
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
                       AND deleted_at IS NULL
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

    public function search(string $query, ?string $tipoProfilo = null): array
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
                WHERE s.deleted_at IS NULL 
                AND (s.nome LIKE ? 
                   OR s.cognome LIKE ? 
                   OR s.codice_fiscale LIKE ? 
                   OR s.matricola LIKE ? 
                   OR s.email LIKE ? 
                   OR s.telefono LIKE ? 
                   OR $concat LIKE ? 
                   OR $concatRev LIKE ?)";

        $params = [$anno, $term, $term, $term, $term, $term, $term, $term, $term];

        if ($tipoProfilo === 'MILITARE') {
            $sql .= " AND (s.grado IS NOT NULL AND s.grado != '')";
        } elseif ($tipoProfilo === 'CIVILE') {
            $sql .= " AND (s.grado IS NULL OR s.grado = '')";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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
                FROM soci s WHERE deleted_at IS NULL";

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

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM soci WHERE deleted_at IS NULL");
        return (int) $stmt->fetchColumn();
    }

    public function findAllPaginated(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("
            SELECT * FROM soci 
            WHERE deleted_at IS NULL 
            ORDER BY cognome, nome
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);

        $soci = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $soci[] = $this->mapRowToSocio($row, false);
        }

        return $soci;
    }

    protected function getTableName(): string
    {
        return 'soci';
    }

    protected function getPrimaryKey(): string
    {
        return 'codice_fiscale';
    }
}
