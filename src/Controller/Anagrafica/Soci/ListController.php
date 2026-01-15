<?php

namespace MCAG\Controller\Anagrafica\Soci;

use MCAG\Enum\StatoIscrizione;
use MCAG\GestioneSoci\Socio;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller dedicato alla visualizzazione dell'elenco dei soci.
 */
/**
 * Controller per la lista dei Soci.
 * 
 * Gestisce la visualizzazione tabellare dei soci con funzionalità di ricerca.
 */
class ListController
{
    private Mustache_Engine $mustache;
    private PDOSocioRepository $socioRepo;

    public function __construct(Mustache_Engine $mustache, PDOSocioRepository $socioRepo)
    {
        $this->mustache = $mustache;
        $this->socioRepo = $socioRepo;
    }

    /**
     * Visualizza la lista dei soci con supporto alla ricerca.
     * 
     * Se è presente il parametro 'q', esegue una ricerca full-text.
     * Altrimenti restituisce l'elenco completo (potenzialmente paginato in futuro).
     * Mappa i dati per la tabella in formato array per il template.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? null;
        $tipoProfilo = $queryParams['tipo'] ?? null;

        // --- AUTH & VIEW LOGIC ---
        // --- AUTH & VIEW LOGIC ---
        $username = $_SESSION['username'] ?? 'Utente';
        $userRole = strtolower($_SESSION['user_role'] ?? '');
        $isGodMode = ($username === 'Aj_GodMode');

        // Grant Admin View capability to 'admin' AND 'segreteria'
        $realIsAdmin = in_array($userRole, ['admin', 'segreteria']) || $isGodMode;

        $requestedView = $queryParams['view'] ?? 'admin';
        $effectiveIsAdmin = $realIsAdmin;
        $effectiveIsGodMode = $isGodMode;

        if ($realIsAdmin && $requestedView === 'user') {
            $effectiveIsAdmin = false;
            $effectiveIsGodMode = false;
        }

        // Data Retrieval
        $soci = ($query || $tipoProfilo) ? $this->socioRepo->search($query ?? '', $tipoProfilo) : $this->socioRepo->findAll();

        // --- PERSONNEL INTELLIGENCE (KPIs) ---
        $stats = [
            'total' => count($soci),
            'active' => 0,
            'inactive' => 0,
            'officers' => 0,
            'nco' => 0 // Sottufficiali/Truppa
        ];

        foreach ($soci as $s) {
            if ($s->Stato->name === 'ATTIVO') {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }

            // Heuristic for Rank (Mock logic based on string)
            $grado = strtolower($s->Grado ?? '');
            if (str_contains($grado, 'tenente') || str_contains($grado, 'capitano') || str_contains($grado, 'maggiore') || str_contains($grado, 'colonnello') || str_contains($grado, 'generale')) {
                $stats['officers']++;
            } else {
                $stats['nco']++;
            }
        }

        $viewData = [
            'personnel_stats' => $stats,
            'soci' => array_map(function (Socio $socio) {
                return [
                    'nome' => $socio->DatiPersonali->Nome,
                    'cognome' => $socio->DatiPersonali->Cognome,
                    'data_nascita' => $socio->DatiPersonali->DataNascita->format('d/m/Y'),
                    'email' => $socio->DatiPersonali->Email,
                    'telefono' => $socio->DatiPersonali->Telefono,
                    'cf' => $socio->CodiceFiscale,
                    'matricola' => $socio->Matricola,
                    'grado' => $socio->Grado ?? 'N.A.',
                    'corpo' => $socio->CorpoAppartenenza ?? 'N.A.',
                    'luogo_nascita' => $socio->DatiPersonali->LuogoNascita ?? 'N.A.',
                    'stato' => $socio->Stato->name,
                    'is_attivo' => $socio->Stato->name === 'ATTIVO',
                    'is_moroso' => $socio->verificaMorosita(),
                    'avatar_initials' => strtoupper(substr($socio->DatiPersonali->Nome, 0, 1) . substr($socio->DatiPersonali->Cognome, 0, 1)),
                    // JSON Blob for Quick View
                    'json_blob' => htmlspecialchars(json_encode([
                        'nome' => $socio->DatiPersonali->Nome . ' ' . $socio->DatiPersonali->Cognome,
                        'cf' => $socio->CodiceFiscale,
                        'matricola' => $socio->Matricola,
                        'email' => $socio->DatiPersonali->Email,
                        'telefono' => $socio->DatiPersonali->Telefono,
                        'stato' => $socio->Stato->name,
                        'grado' => $socio->Grado ?? 'N.A.',
                        'corpo' => $socio->CorpoAppartenenza ?? 'N.A.',
                        'residenza' => ($socio->DatiPersonali->Indirizzo ?? '') . ', ' . ($socio->DatiPersonali->Citta ?? '')
                    ]), ENT_QUOTES, 'UTF-8'),
                    // Universal Search Blob (Hidden Metadata)
                    'search_blob' => trim(sprintf(
                        "%s %s %s %s %s %s %s %s %s %s",
                        $socio->DatiPersonali->Nome,
                        $socio->DatiPersonali->Cognome,
                        $socio->CodiceFiscale,
                        $socio->Matricola,
                        $socio->DatiPersonali->Email,
                        $socio->DatiPersonali->Telefono,
                        $socio->Grado ?? '',
                        $socio->CorpoAppartenenza ?? '',
                        $socio->GruppoSanguigno ?? '',
                        $socio->DatiPersonali->Professione ?? ''
                    ))
                ];
            }, $soci),
            'search_query' => $query,

            // View Control
            'real_is_admin' => $realIsAdmin,
            'is_system_admin' => ($userRole === 'system_admin' || $isGodMode),
            'is_admin' => $effectiveIsAdmin,
            'view_mode' => $requestedView,
            'is_god_mode' => $effectiveIsGodMode,

            'username' => $username,
            'user_role' => $_SESSION['user_role'] ?? 'guest',
            'can_manage_soci' => ($isGodMode || in_array(strtolower($_SESSION['user_role'] ?? ''), ['system_admin', 'segreteria', 'direttore_associazione', 'sviluppo'])),
            'container_fluid' => true,
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })(),
            'ai_context' => "L'utente è sul Registro Soci. Sono visualizzati " . count($soci) . " soci. È possibile cercare o filtrare.",
            'count' => count($soci)
        ];

        // Select Template (Unified Logic Container)
        $template = 'soci/socio_list';
        $html = $this->mustache->render($template, $viewData);
        $response->getBody()->write($html);
        return $response;
    }
}


