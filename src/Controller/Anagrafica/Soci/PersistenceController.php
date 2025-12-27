<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Soci;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\Service\RegistrationService;
use FratellanzaMilitare\Service\ValidationService;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla persistenza e gestione dei dati dei soci.
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

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
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

    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        if (!$socio)
            return $response->withStatus(404);

        $viewData = [
            'socio' => [
                'codice_fiscale' => $socio->CodiceFiscale,
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'data_nascita' => $socio->DatiPersonali->DataNascita->format('Y-m-d'),
                'indirizzo' => $socio->DatiPersonali->Indirizzo,
                'email' => $socio->DatiPersonali->Email,
                'telefono' => $socio->DatiPersonali->Telefono,
                'matricola' => $socio->Matricola
            ],
            'csrf' => ['name' => $request->getAttribute('csrf_name'), 'value' => $request->getAttribute('csrf_value')],
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin'
        ];

        $html = $this->mustache->render('socio_edit', $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        if (!$socio)
            return $response->withStatus(404);

        $data = $request->getParsedBody();

        // Semplificazione aggiornamento per brevità (logica identica all'originale)
        $socio->DatiPersonali->Nome = strtoupper($data['nome']);
        $socio->DatiPersonali->Cognome = strtoupper($data['cognome']);
        if (!empty($data['data_nascita']))
            $socio->DatiPersonali->DataNascita = new \DateTime($data['data_nascita']);
        $socio->DatiPersonali->Indirizzo = $data['indirizzo'] ?? '';
        $socio->DatiPersonali->Email = $data['email'] ?? '';
        $socio->DatiPersonali->Telefono = $data['telefono'] ?? '';
        $socio->Matricola = $data['matricola'] ?? $socio->Matricola;

        $this->socioRepo->save($socio);
        $this->auditLogger->info("Socio aggiornato: {$socio->CodiceFiscale}");

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_list'))->withStatus(302);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->socioRepo->delete($args['cf']);
        $this->auditLogger->info("Socio eliminato: {$args['cf']}");
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_list'))->withStatus(302);
    }
}
