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
        $username = $_SESSION['username'] ?? 'Utente';
        $isGodMode = ($username === 'Aj_GodMode');
        $realIsAdmin = (($_SESSION['user_role'] ?? '') === 'admin') || $isGodMode;

        $requestedView = $queryParams['view'] ?? 'admin';
        $effectiveIsAdmin = $realIsAdmin;
        $effectiveIsGodMode = $isGodMode;

        if ($realIsAdmin && $requestedView === 'user') {
            $effectiveIsAdmin = false;
            $effectiveIsGodMode = false;
        }

        // Data Retrieval
        $soci = ($query || $tipoProfilo) ? $this->socioRepo->search($query ?? '', $tipoProfilo) : $this->socioRepo->findAll();

        $viewData = [
            'soci' => array_map(function (Socio $socio) {
                return [
                    'nome' => $socio->DatiPersonali->Nome,
                    'cognome' => $socio->DatiPersonali->Cognome,
                    'data_nascita' => $socio->DatiPersonali->DataNascita->format('d/m/Y'),
                    'email' => $socio->DatiPersonali->Email,
                    'telefono' => $socio->DatiPersonali->Telefono,
                    'cf' => $socio->CodiceFiscale,
                    'matricola' => $socio->Matricola,
                    'stato' => $socio->Stato->name,
                    'is_attivo' => $socio->Stato->name === 'ATTIVO', // ENUM comparison logic
                    'is_moroso' => $socio->verificaMorosita(),
                ];
            }, $soci),
            'search_query' => $query,

            // View Control
            'real_is_admin' => $realIsAdmin,
            'is_admin' => $effectiveIsAdmin,
            'view_mode' => $requestedView,
            'is_god_mode' => $effectiveIsGodMode,

            'username' => $username,
            'user_initial' => strtoupper(substr($username, 0, 1)),
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


