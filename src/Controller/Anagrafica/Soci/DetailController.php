<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Soci;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
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
            return [
                'id' => $doc->IdUnivoco,
                'tipo' => (new \ReflectionClass($doc))->getShortName(),
                'nome_file' => $doc->NomeFile,
                'stato' => $doc->Stato->name,
                'socio_cf' => $socio->CodiceFiscale,
                'csrf_name' => $csrfName,
                'csrf_value' => $csrfValue
            ];
        }, $socio->DocumentiAssociati);

        $html = $this->mustache->render('socio_detail', [
            'socio' => [
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'cf' => $socio->CodiceFiscale,
            ],
            'documenti' => $docs,
            'csrf' => ['name' => $csrfName, 'value' => $csrfValue],
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
