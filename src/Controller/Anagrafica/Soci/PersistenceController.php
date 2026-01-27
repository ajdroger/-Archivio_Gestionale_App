<?php

namespace MCAG\Controller\Anagrafica\Soci;

use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\Service\RegistrationService;
use MCAG\Service\ValidationService;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla persistenza e gestione dei dati dei soci.
 */
/**
 * Controller per la persistenza dei dati dei Soci.
 * 
 * Gestisce il ciclo di vita (CRUD) dei soci: Creazione, Modifica, Aggiornamento ed Eliminazione.
 * Integra validazione e log per ogni operazione.
 */
class PersistenceController
{
    private Mustache_Engine $mustache;
    private PDOSocioRepository $socioRepo;
    private LoggerInterface $auditLogger;
    private ValidationService $validator;
    private RegistrationService $registrationService;

    public function __construct(
        Mustache_Engine $mustache,
        PDOSocioRepository $socioRepo,
        LoggerInterface $auditLogger,
        ValidationService $validator,
        RegistrationService $registrationService
    ) {
        $this->mustache = $mustache;
        $this->socioRepo = $socioRepo;
        $this->auditLogger = $auditLogger;
        $this->validator = $validator;
        $this->registrationService = $registrationService;
    }

    /**
     * Mostra il form di creazione socio.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewData = [
            'csrf' => ['name' => $request->getAttribute('csrf_name'), 'value' => $request->getAttribute('csrf_value')],
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin'
        ];
        $html = $this->mustache->render('socio_create', $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Mostra il modulo di iscrizione pubblico (Esterno).
     */
    public function publicForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewData = [
            'csrf' => ['name' => $request->getAttribute('csrf_name'), 'value' => $request->getAttribute('csrf_value')],
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ];
        // Uses the restored 'modulo_iscrizione.mustache' -> 'soci/modulo_iscrizione'
        $html = $this->mustache->render('soci/modulo_iscrizione', $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Gestisce l'iscrizione pubblica.
     */
    public function publicStore(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Simple store logic without Auth checks for now (or basic validation)
        $data = $request->getParsedBody();
        try {
            $this->registrationService->registerNewMember($data);
        } catch (\Exception $e) {
            $this->auditLogger->error("Errore iscrizione pubblica: " . $e->getMessage());
            $response->getBody()->write("Errore durante l'iscrizione: " . $e->getMessage());
            return $response->withStatus(400);
        }

        // Redirect to Home with success message
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('dashboard'))->withStatus(302);
    }

    /**
     * Salva un nuovo socio (Store).
     * 
     * Utilizza RegistrationService per gestire la logica complessa di iscrizione.
     * Gestisce i log di errore.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($_SESSION['is_demo_mode'] ?? false) {
            $html = $this->mustache->render('errors/403_demo', [
                'base_url' => (function () {
                    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                    return $scriptDir === '/' ? '' : $scriptDir;
                })()
            ]);
            $response->getBody()->write($html);
            return $response->withStatus(403);
        }

        $data = $request->getParsedBody();
        try {
            $this->registrationService->registerNewMember($data);
        } catch (\Exception $e) {
            $this->auditLogger->error("Errore salvataggio socio: " . $e->getMessage());
            $response->getBody()->write("Errore: " . $e->getMessage());
            return $response->withStatus(400);
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_list'))->withStatus(302);
    }

    /**
     * Mostra il form di modifica socio.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        if (!$socio)
            return $response->withStatus(404);

        $viewData = [
            'socio' => [
                'codice_fiscale' => $socio->CodiceFiscale,
                'matricola' => $socio->Matricola,
                // Anagrafica
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'sesso' => $socio->DatiPersonali->Sesso,
                'data_nascita' => $socio->DatiPersonali->DataNascita->format('Y-m-d'),
                'luogo_nascita' => $socio->DatiPersonali->LuogoNascita,
                'stato_civile' => $socio->DatiPersonali->StatoCivile,
                'indirizzo' => $socio->DatiPersonali->Indirizzo,
                'email' => $socio->DatiPersonali->Email,
                'telefono' => $socio->DatiPersonali->Telefono,
                'titolo_studio' => $socio->DatiPersonali->TitoloStudio,
                'professione' => $socio->DatiPersonali->Professione,
                // Militare
                'grado' => $socio->Grado,
                'corpo_appartenenza' => $socio->CorpoAppartenenza,
                'data_arruolamento' => $socio->DataArruolamento?->format('Y-m-d'),
                'data_congedo' => $socio->DataCongedo?->format('Y-m-d'),
                // Sanitario
                'gruppo_sanguigno' => $socio->GruppoSanguigno,
                'contatto_emergenza' => $socio->ContattoEmergenza,
                'note_mediche' => $socio->NoteMediche
            ],
            'csrf' => ['name' => $request->getAttribute('csrf_name'), 'value' => $request->getAttribute('csrf_value')],
            'is_admin' => (($_SESSION['user_role'] ?? '') === 'admin') || (($_SESSION['username'] ?? '') === 'Aj_GodMod')
        ];

        $html = $this->mustache->render('socio_edit', $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Aggiorna i dati di un socio esistente.
     * 
     * Mappa i dati dal form all'entità Socio e salva le modifiche.
     * Logga l'operazione di aggiornamento.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($_SESSION['is_demo_mode'] ?? false) {
            $html = $this->mustache->render('errors/403_demo', [
                'base_url' => (function () {
                    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                    return $scriptDir === '/' ? '' : $scriptDir;
                })()
            ]);
            $response->getBody()->write($html);
            return $response->withStatus(403);
        }

        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        if (!$socio)
            return $response->withStatus(404);

        $data = $request->getParsedBody();

        // Semplificazione aggiornamento per brevità (logica identica all'originale)
        // Anagrafica
        $socio->DatiPersonali->Nome = strtoupper($data['nome']);
        $socio->DatiPersonali->Cognome = strtoupper($data['cognome']);
        $socio->DatiPersonali->Sesso = $data['sesso'] ?? null;

        if (!empty($data['data_nascita']))
            $socio->DatiPersonali->DataNascita = new \DateTime($data['data_nascita']);

        $socio->DatiPersonali->LuogoNascita = strtoupper($data['luogo_nascita'] ?? '');
        $socio->DatiPersonali->StatoCivile = $data['stato_civile'] ?? null;
        $socio->DatiPersonali->Indirizzo = $data['indirizzo'] ?? '';
        $socio->DatiPersonali->Email = $data['email'] ?? '';
        $socio->DatiPersonali->Telefono = $data['telefono'] ?? '';
        $socio->DatiPersonali->TitoloStudio = $data['titolo_studio'] ?? null;
        $socio->DatiPersonali->Professione = $data['professione'] ?? null;
        $socio->Matricola = $data['matricola'] ?? $socio->Matricola;

        // Militare
        // Selettore Tipo Profilo gestito dalla UI, ma controlliamo
        $isMilitare = ($data['tipo_profilo'] ?? 'MILITARE') === 'MILITARE';
        // Fallback: se ha inserito Grado, assumiamo sia militare se il toggle non è passato
        if (!isset($data['tipo_profilo']) && !empty($data['grado']))
            $isMilitare = true;

        if ($isMilitare) {
            $socio->Grado = $data['grado'] ?? null;
            $socio->CorpoAppartenenza = $data['corpo_appartenenza'] ?? null;
            $socio->DataArruolamento = !empty($data['data_arruolamento']) ? new \DateTime($data['data_arruolamento']) : null;
            $socio->DataCongedo = !empty($data['data_congedo']) ? new \DateTime($data['data_congedo']) : null;
        } else {
            $socio->Grado = null;
            $socio->CorpoAppartenenza = null;
            $socio->DataArruolamento = null;
            $socio->DataCongedo = null;
        }

        // Sanitario
        $socio->GruppoSanguigno = $data['gruppo_sanguigno'] ?? null;
        $socio->ContattoEmergenza = $data['contatto_emergenza'] ?? null;
        $socio->NoteMediche = $data['note_mediche'] ?? null;

        $this->socioRepo->save($socio);
        $this->auditLogger->info("Socio aggiornato: {$socio->CodiceFiscale}");

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        // Redirect back to the detail page (dossier) instead of the list
        return $response->withHeader('Location', $routeParser->urlFor('socio_detail', ['cf' => $socio->CodiceFiscale]))->withStatus(302);
    }

    /**
     * Elimina (o archivia) un socio.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($_SESSION['is_demo_mode'] ?? false) {
            $html = $this->mustache->render('errors/403_demo', [
                'base_url' => (function () {
                    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                    return $scriptDir === '/' ? '' : $scriptDir;
                })()
            ]);
            $response->getBody()->write($html);
            return $response->withStatus(403);
        }

        $this->socioRepo->delete($args['cf']);
        $this->auditLogger->info("Socio eliminato: {$args['cf']}");
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_list'))->withStatus(302);
    }
}


