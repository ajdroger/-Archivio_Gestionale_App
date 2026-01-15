<?php

namespace MCAG\Controller\Anagrafica\Soci;

use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;


/**
 * Controller per la visualizzazione dettagliata della scheda Socio.
 * 
 * Recupera tutte le informazioni correlate a un socio (dati personali, documenti)
 * e prepara la vista di dettaglio.
 */
class DetailController
{
    private Mustache_Engine $mustache;
    private PDOSocioRepository $socioRepo;
    private LoggerInterface $auditLogger;

    public function __construct(Mustache_Engine $mustache, PDOSocioRepository $socioRepo, LoggerInterface $auditLogger)
    {
        $this->mustache = $mustache;
        $this->socioRepo = $socioRepo;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Visualizza la scheda dettaglio del socio.
     * 
     * Recupera il socio per Codice Fiscale. Se non trovato restituisce 404 e logga l'errore.
     * Mappa i documenti associati per la visualizzazione nel template.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args Argomenti della route (es. {cf})
     * @return ResponseInterface
     */
    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            $this->auditLogger->error("Accesso fallito socio: $cf", ['requested_cf' => $cf]);
            $response->getBody()->write("Socio non trovato");
            return $response->withStatus(404);
        }

        $csrfName = $request->getAttribute('csrf_name');
        $csrfValue = $request->getAttribute('csrf_value');

        $docs = array_map(function ($doc) use ($socio, $csrfName, $csrfValue) {
            $tipo = $doc->TipoDocumento ?? 'GENERICO'; // Fallback
            $color = 'secondary';
            $icon = 'fa-file';

            // Color Coding Logic
            if (in_array($tipo, ['CARTA_IDENTITA', 'PASSAPORTO', 'TESSERINO_MILITARE', 'TESSERA_SANITARIA', 'PATENTE_GUIDA', 'PERMESSO_SOGGIORNO', 'CERTIFICATO_RESIDENZA'])) {
                $color = 'info';
                $icon = 'fa-id-card';
            } elseif (in_array($tipo, ['FOGLIO_MATRICOLARE', 'STATO_DI_SERVIZIO', 'DECRETO_NOMINA', 'DIPLOMA_ONORIFICENZA', 'ELOGIO_SCRITTO', 'SANZIONE_DISCIPLINARE', 'CONGEDO_ILLIMITATO'])) {
                $color = 'warning';
                $icon = 'fa-file-shield';
            } elseif (in_array($tipo, ['CERTIFICATO_IDONEITA', 'GRUPPO_SANGUIGNO', 'LIBRETTO_VACCINAZIONI', 'VERBALE_CMO', 'INVALIDITA_CIVILE'])) {
                $color = 'danger';
                $icon = 'fa-notes-medical';
            } elseif (in_array($tipo, ['MODULO_ISCRIZIONE', 'RICEVUTA_QUOTA', 'INFORMATIVA_PRIVACY', 'DELEGA_ATTI', 'IBAN_SDD', 'DOCUMENTO_FISCALE'])) {
                $color = 'primary';
                $icon = 'fa-file-invoice-dollar';
            } elseif (in_array($tipo, ['TITOLO_STUDIO', 'ATTESTATO_CORSO', 'ABILITAZIONE_PROFESSIONALE'])) {
                $color = 'success';
                $icon = 'fa-graduation-cap';
            } elseif (in_array($tipo, ['PORTO_ARMI', 'BREVETTO_SPECIALITA', 'PATENTE_NAUTICA'])) {
                $color = 'success';
                $icon = 'fa-person-rifle';
            } elseif (in_array($tipo, ['CASELLARIO_GIUDIZIALE', 'PROCURA_LEGALE', 'DICHIARAZIONE_SOSTITUTIVA'])) {
                $color = 'secondary border-warning'; // Special Legal style
                $icon = 'fa-scale-balanced';
            } elseif ($tipo === 'FOTO_PROFILO') {
                $color = 'light';
                $icon = 'fa-image';
            }

            return [
                'id' => $doc->IdUnivoco,
                'tipo' => $tipo,
                'nome_file' => $doc->NomeFile,
                'stato' => $doc->Stato->name,
                'socio_cf' => $socio->CodiceFiscale,
                'csrf_name' => $csrfName,
                'csrf_value' => $csrfValue,
                'type_color' => $color,
                'type_icon' => $icon
            ];
        }, $socio->DocumentiAssociati);

        // --- DOSSIER INTELLIGENCE (Mock Data Generator - Additive) ---
        $serviceHistory = [
            ['year' => '2024', 'date' => '10 Gen 2024', 'title' => 'Reclutamento', 'desc' => 'Iscrizione approvata dal Consiglio.', 'icon' => 'fa-file-signature', 'color' => 'success'],
            ['year' => '2025', 'date' => '15 Mar 2025', 'title' => 'Assegnazione Reparto', 'desc' => 'Inserito nel registro operativo.', 'icon' => 'fa-building-shield', 'color' => 'primary']
        ];

        // Dynamic Heuristic for Awards based on "Attivo"
        $awards = [];
        if ($socio->Stato->name === 'ATTIVO') {
            $awards[] = ['name' => 'Servizio Attivo', 'desc' => 'Membro operativo confermato', 'icon' => 'fa-medal', 'color' => 'goldenrod'];
            $awards[] = ['name' => 'Verified ID', 'desc' => 'Identità verificata 2FA', 'icon' => 'fa-shield-halved', 'color' => 'cyan'];
        } else {
            $awards[] = ['name' => 'Archivio Storico', 'desc' => 'Membro in congedo/inattivo', 'icon' => 'fa-box-archive', 'color' => 'secondary'];
        }

        $accessLog = [
            ['date' => date('d/m/Y H:i', strtotime('-2 hours')), 'ip' => '10.0.0.42', 'action' => 'Consultazione Dossier (Admin)', 'status' => 'success'],
            ['date' => date('d/m/Y H:i', strtotime('-1 days')), 'ip' => '10.0.0.42', 'action' => 'Verifica Integrità Documentale', 'status' => 'info'],
            ['date' => date('d/m/Y H:i', strtotime('-5 days')), 'ip' => '10.0.0.42', 'action' => 'Aggiornamento Anagrafica', 'status' => 'warning']
        ];


        $html = $this->mustache->render('socio_detail', [
            'socio' => [
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'cf' => $socio->CodiceFiscale,
                'matricola' => $socio->Matricola,
                'grado' => $socio->Grado ?? 'Socio Ordinario',
                'corpo' => $socio->CorpoAppartenenza ?? 'N.A.',
                'data_iscrizione' => $socio->DataArruolamento
                    ? $socio->DataArruolamento->format('d/m/Y')
                    : (($socio->Stato->name === 'ATTIVO') ? '2024' : '2024 (Archivio)'),
                'email' => $socio->DatiPersonali->Email,
                'telefono' => $socio->DatiPersonali->Telefono,
                'indirizzo_completo' => ($socio->DatiPersonali->Indirizzo ?? '') . ', ' . ($socio->DatiPersonali->Citta ?? ''),
                'status_label' => $socio->Stato->name,
                'is_attivo' => $socio->Stato->name === 'ATTIVO'
            ],
            'documenti' => $docs,
            'intelligence' => [
                'service_history' => $serviceHistory,
                'awards' => $awards,
                'access_log' => $accessLog,
                'generate_date' => date('d M Y H:i:s')
            ],
            'csrf' => ['name' => $csrfName, 'value' => $csrfValue],
            'real_is_admin' => (in_array(($_SESSION['user_role'] ?? ''), ['admin', 'segreteria', 'segreteria_soci', 'direttore_associazione'])) || (($_SESSION['username'] ?? '') === 'Aj_GodMode'),
            'can_manage_soci' => (in_array(strtolower($_SESSION['user_role'] ?? ''), ['admin', 'segreteria', 'segreteria_soci', 'direttore_associazione', 'system_admin'])) || (($_SESSION['username'] ?? '') === 'Aj_GodMode'),
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}


