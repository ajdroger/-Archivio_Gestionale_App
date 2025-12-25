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
    private bool $isMysql;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseConnection::getConnection();
        $this->docRepo = new PDODocumentoRepository($this->pdo);
        $this->isMysql = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    public function save(Socio $socio): void
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }
        try {

            // Determine Status Column Name
            $statusCol = $this->isMysql ? 'stato_iscrizione' : 'stato';

            $sql = "";
            if ($this->isMysql) {
                // MySQL
                $sql = "INSERT INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, telefono, $statusCol) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        matricola=VALUES(matricola), nome=VALUES(nome), cognome=VALUES(cognome), 
                        data_nascita=VALUES(data_nascita), indirizzo=VALUES(indirizzo), email=VALUES(email), 
                        telefono=VALUES(telefono), $statusCol=VALUES($statusCol)";
            } else {
                // SQLite
                $sql = "INSERT OR REPLACE INTO soci (codice_fiscale, matricola, nome, cognome, data_nascita, indirizzo, email, telefono, $statusCol) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }

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
            // 1. Get existing IDs from DB
            $existingDocs = $this->docRepo->findBySocio($socio->CodiceFiscale);
            $existingIds = array_map(fn($d) => $d->IdUnivoco, $existingDocs);

            // 2. Get current IDs from Entity
            $currentIds = array_map(fn($d) => $d->IdUnivoco, $socio->DocumentiAssociati);

            // 3. Find IDs to delete (in DB but not in Entity)
            $idsToDelete = array_diff($existingIds, $currentIds);
            foreach ($idsToDelete as $id) {
                $this->docRepo->delete($id);
            }

            // 4. Save current documents (Insert/Update)
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
        // Portable Subquery
        $sql = "SELECT s.*, 
                (SELECT 1 FROM documenti d 
                 WHERE d.socio_cf = s.codice_fiscale 
                 AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                 AND d.anno_solare = ? 
                 AND d.stato = 'VALIDATO' 
                 LIMIT 1) as is_pagato
                FROM soci s";

        // Note: 'documenti' table column 'codice_fiscale_socio' was renamed to 'socio_cf' in MySQL Migration.
        // We must ensure the query uses the correct column name.
        // But if I want to support BOTH (for fallback), I check schema or driver.
        // Since migration changed the name in MySQL, and I am rewriting this file, I should use 'socio_cf' for MySQL and 'codice_fiscale_socio' for SQLite?
        // Or I should have kept the column name same.
        // I will use a helper property for column name.

        $fkCol = $this->isMysql ? 'socio_cf' : 'codice_fiscale_socio';
        $sql = str_replace('d.socio_cf', "d.$fkCol", $sql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anno]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soci = [];
        foreach ($rows as $row) {
            $soci[] = $this->mapRowToSocio($row, false); // Skip heavy document hydration
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
            // MySQL uses 'stato_iscrizione', SQLite uses 'stato'
            // Check both
            $statusName = $row['stato'] ?? ($row['stato_iscrizione'] ?? 'IN_ATTESA');
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
        $fkCol = $this->isMysql ? 'socio_cf' : 'codice_fiscale_socio';
        $statusCol = $this->isMysql ? 'stato_iscrizione' : 'stato';

        // Total Soci
        $total = $this->pdo->query("SELECT COUNT(*) FROM soci")->fetchColumn();

        // Active Soci
        $stmtActive = $this->pdo->prepare("SELECT COUNT(*) FROM soci WHERE $statusCol = ?");
        $stmtActive->execute([StatoIscrizione::ATTIVO->name]);
        $attivi = $stmtActive->fetchColumn();

        // Paganti
        $sqlPaganti = "SELECT COUNT(*) FROM soci s 
                       WHERE EXISTS (
                           SELECT 1 FROM documenti d 
                           WHERE d.$fkCol = s.codice_fiscale 
                           AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                           AND d.anno_solare = ? 
                           AND d.stato = 'VALIDATO'
                       )";
        $stmtPaganti = $this->pdo->prepare($sqlPaganti);
        $stmtPaganti->execute([$annoCorrente]);
        $paganti = $stmtPaganti->fetchColumn();

        $morosi = $total - $paganti;

        // 1. Financial Trend
        // SQLite: strftime('%m', data). MySQL: MONTH(data)
        $monthExpr = $this->isMysql ? "MONTH(data_caricamento)" : "strftime('%m', data_caricamento)";

        $sqlTrend = "SELECT $monthExpr as mese, COUNT(*) as count 
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

        // 2. Demographics
        // Age Calc
        if ($this->isMysql) {
            $ageExpr = "TIMESTAMPDIFF(YEAR, data_nascita, CURDATE())";
        } else {
            $ageExpr = "cast(strftime('%Y.%m%d', 'now') - strftime('%Y.%m%d', data_nascita) as int)";
        }

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
                       WHERE $statusCol = 'ATTIVO'
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
            'trend_iscritti' => array_values($trend),
            'demografica' => $demografica,
            'data_riferimento' => date('d/m/Y H:i')
        ];
    }

    public function search(string $query): array
    {
        $term = "%$query%";
        $anno = (int) date('Y');
        $fkCol = $this->isMysql ? 'socio_cf' : 'codice_fiscale_socio';

        // Concat
        $concat = $this->isMysql ? "CONCAT(s.nome, ' ', s.cognome)" : "(s.nome || ' ' || s.cognome)";
        $concatRev = $this->isMysql ? "CONCAT(s.cognome, ' ', s.nome)" : "(s.cognome || ' ' || s.nome)";

        $sql = "SELECT s.*, 
                (SELECT 1 FROM documenti d 
                 WHERE d.$fkCol = s.codice_fiscale 
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
        $fkCol = $this->isMysql ? 'socio_cf' : 'codice_fiscale_socio';
        $statusCol = $this->isMysql ? 'stato_iscrizione' : 'stato';

        $sql = "SELECT s.*, 
                (SELECT 1 FROM documenti d 
                 WHERE d.$fkCol = s.codice_fiscale 
                 AND d.tipo_documento = 'MODULO_ISCRIZIONE' 
                 AND d.anno_solare = ? 
                 AND d.stato = 'VALIDATO' 
                 LIMIT 1) as is_pagato
                FROM soci s WHERE 1=1";

        $params = [$anno];

        if (!empty($filters['stato'])) {
            $sql .= " AND s.$statusCol = ?";
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

    /**
     * Hard delete socio from database (GDPR Art. 17 - Right to Erasure)
     * Permanently removes all personal data including documents from filesystem
     * 
     * @param string $codiceFiscale
     * @return bool Success
     */
    public function hardDelete(string $codiceFiscale): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Get and delete all associated document files
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $cfCol = ($driver === 'mysql') ? 'socio_cf' : 'codice_fiscale_socio';
            $stmtDocs = $this->pdo->prepare("SELECT percorso_file FROM documenti WHERE $cfCol = ?");
            $stmtDocs->execute([$codiceFiscale]);
            $files = $stmtDocs->fetchAll(PDO::FETCH_COLUMN);

            foreach ($files as $file) {
                if ($file && file_exists($file)) {
                    @unlink($file);
                }
            }

            // 2. Delete from documenti table (CASCADE should handle, but explicit for clarity)
            $colName = ($driver === 'mysql') ? 'socio_cf' : 'codice_fiscale_socio';
            $stmtDelDocs = $this->pdo->prepare("DELETE FROM documenti WHERE $colName = ?");
            $stmtDelDocs->execute([$codiceFiscale]);

            // 3. Delete from soci table
            $stmtDelSocio = $this->pdo->prepare("DELETE FROM soci WHERE codice_fiscale = ?");
            $stmtDelSocio->execute([$codiceFiscale]);

            $this->pdo->commit();

            // 5. Audit log (pseudonymized for compliance, system action)
            $pseudoCF = substr($codiceFiscale, 0, 3) . '***' . substr($codiceFiscale, -3);
            $trail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
            $trail->logEvento(
                null, // System action, no user
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

    /**
     * Export all personal data for a single socio (GDPR Art. 15 & 20)
     * Returns machine-readable JSON format for data portability
     * 
     * @param string $codiceFiscale
     * @return array Complete data export
     */
    public function exportGDPRData(string $codiceFiscale): array
    {
        $socio = $this->findByCodiceFiscale($codiceFiscale);
        if (!$socio) {
            return [];
        }

        // Fetch additional data from database (dates not in Socio object)
        $statusCol = $this->isMysql ? 'stato_iscrizione' : 'stato';
        $stmt = $this->pdo->prepare("SELECT data_iscrizione, data_scadenza FROM soci WHERE codice_fiscale = ?");
        $stmt->execute([$codiceFiscale]);
        $dbData = $stmt->fetch(PDO::FETCH_ASSOC);

        $docRepo = new PDODocumentoRepository($this->pdo);

        // Safe document loading with error handling
        try {
            $documents = $docRepo->findBySocio($codiceFiscale);
        } catch (\Exception $e) {
            // If document loading fails, continue with empty array
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
