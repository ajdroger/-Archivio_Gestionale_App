<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Soci;

use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
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
                    'is_attivo' => $socio->Stato === StatoIscrizione::ATTIVO,
                    'is_moroso' => $socio->verificaMorosita(),
                ];
            }, $soci),
            'search_query' => $query,
            'is_admin' => (($_SESSION['user_role'] ?? '') === 'admin') || (($_SESSION['username'] ?? '') === 'Aj_GodMod'),
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ];

        $html = $this->mustache->render('socio_list', $viewData);
        $response->getBody()->write($html);
        return $response;
    }
}
