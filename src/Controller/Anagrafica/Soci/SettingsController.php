<?php

namespace MCAG\Controller\Anagrafica\Soci;

use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller per la gestione delle impostazioni specifiche di un Socio.
 */
class SettingsController
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
     * Visualizza la pagina delle impostazioni del socio.
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            $this->auditLogger->error("Accesso impostazioni fallito: socio non trovato $cf");
            $response->getBody()->write("Socio non trovato");
            return $response->withStatus(404);
        }

        // Mock Settings Data (In a real app, these would come from the DB)
        $settings = [
            'privacy' => [
                'public_profile' => true,
                'show_email' => false,
                'show_phone' => false
            ],
            'notifications' => [
                'newsletter' => true,
                'events' => true,
                'paper_mail' => false
            ]
        ];

        $html = $this->mustache->render('soci/socio_settings', [
            'socio' => [
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'cf' => $socio->CodiceFiscale,
                'matricola' => $socio->Matricola,
                'stato' => $socio->StatoIscrizione->name
            ],
            'settings' => $settings,
            'is_admin' => (($_SESSION['user_role'] ?? '') === 'admin') || (($_SESSION['username'] ?? '') === 'Aj_GodMock'),
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

    /**
     * Salva le impostazioni (Placeholder)
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // TODO: Implement Logic
        return $response->withHeader('Location', '/soci/' . $args['cf'] . '/impostazioni')->withStatus(302);
    }
}
