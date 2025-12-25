<?php

namespace FratellanzaMilitare\Controller;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\GestioneSoci\Socio;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SocioController
{
    private Mustache_Engine $mustache;
    private PDOSocioRepository $socioRepo;
    private \Psr\Log\LoggerInterface $auditLogger;
    private \FratellanzaMilitare\Service\ValidationService $validator;
    private \FratellanzaMilitare\Service\RegistrationService $registrationService;

    public function __construct(
        Mustache_Engine $mustache,
        PDOSocioRepository $socioRepo,
        \Psr\Log\LoggerInterface $auditLogger,
        \FratellanzaMilitare\Service\ValidationService $validator,
        \FratellanzaMilitare\Service\RegistrationService $registrationService
    ) {
        $this->mustache = $mustache;
        $this->socioRepo = $socioRepo;
        $this->auditLogger = $auditLogger;
        $this->validator = $validator;
        $this->registrationService = $registrationService;
    }

    public function calculateFiscalCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['nome']) || empty($data['cognome']) || empty($data['data_nascita']) || empty($data['sesso']) || empty($data['luogo'])) {
            $response->getBody()->write(json_encode(['error' => 'Dati incompleti per il calcolo.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $calculator = new \FratellanzaMilitare\Service\FiscalCodeCalculator();
            $cf = $calculator->calculate(
                $data['nome'],
                $data['cognome'],
                $data['data_nascita'],
                $data['sesso'],
                $data['luogo']
            );

            $response->getBody()->write(json_encode(['cf' => $cf]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            // Errori di validazione (es. Comune non trovato)
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Exception $e) {
            // General Error
            $this->auditLogger->error("Errore Calcolo CF: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Errore interno nel calcolo.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? null;

        if ($query) {
            $soci = $this->socioRepo->search($query);
        } else {
            $soci = $this->socioRepo->findAll();
        }



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
                    'is_attivo' => $socio->Stato === \FratellanzaMilitare\Enum\StatoIscrizione::ATTIVO,
                    'is_moroso' => $socio->verificaMorosita(),
                ];
            }, $soci),
            'search_query' => $query,


            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ];

        $html = $this->mustache->render('socio_list', $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            $this->auditLogger->error("Tentativo di accesso a socio inesistente: $cf", ['requested_cf' => $cf]);
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

        $csrfName = $request->getAttribute('csrf_name');
        $csrfValue = $request->getAttribute('csrf_value');

        $html = $this->mustache->render('socio_detail', [
            'socio' => [
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'cf' => $socio->CodiceFiscale,
            ],
            'documenti' => $docs,
            'csrf' => [
                'name' => $csrfName,
                'value' => $csrfValue
            ],
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // CSRF handling - Standard Slim CSRF keys
        $csrfName = $request->getAttribute('csrf_name');
        $csrfValue = $request->getAttribute('csrf_value');

        $viewData = [
            'csrf' => [
                'name' => $csrfName,
                'value' => $csrfValue
            ],
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
            $socio = $this->registrationService->registerNewMember($data);

            // Audit Log handled inside Service, but we can log controller action if needed
            // $this->auditLogger->info("Controller: Registration completed for {$socio->CodiceFiscale}");

        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write("Errore Validazione: " . $e->getMessage());
            return $response->withStatus(400);
        } catch (\Exception $e) {
            $this->auditLogger->error("Errore disastroso durante registrazione: " . $e->getMessage());
            $response->getBody()->write("Errore di sistema durante il salvataggio.");
            return $response->withStatus(500);
        }

        $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
        return $response
            ->withHeader('Location', $routeParser->urlFor('socio_list'))
            ->withStatus(302);
    }

    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            $response->getBody()->write("Socio non trovato");
            return $response->withStatus(404);
        }

        $csrfName = $request->getAttribute('csrf_name');
        $csrfValue = $request->getAttribute('csrf_value');

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
            'csrf' => [
                'name' => $csrfName,
                'value' => $csrfValue
            ],
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin'
        ];

        $html = $this->mustache->render('socio_edit', $viewData);
        $response->getBody()->write($html);
        return $response;
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $data = $request->getParsedBody();
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            return $response->withStatus(404);
        }

        // Strict Validation for Update
        if (!$this->validator->isValidCodiceFiscale($cf)) {
            return $response->withStatus(400);
        }

        if (!empty($data['email']) && !$this->validator->isValidEmail($data['email'])) {
            $response->getBody()->write("Errore: L'indirizzo email fornito non è valido.");
            return $response->withStatus(400);
        }

        // Update fields
        $socio->DatiPersonali->Nome = strtoupper($data['nome']);
        $socio->DatiPersonali->Cognome = strtoupper($data['cognome']);
        if (!empty($data['data_nascita'])) {
            $socio->DatiPersonali->DataNascita = new \DateTime($data['data_nascita']);
        }
        $socio->DatiPersonali->Indirizzo = $data['indirizzo'] ?? '';
        $socio->DatiPersonali->Email = $data['email'] ?? '';
        $socio->DatiPersonali->Telefono = $data['telefono'] ?? '';
        $socio->Matricola = $data['matricola'] ?? $socio->Matricola;

        try {
            $this->socioRepo->save($socio);
            $this->auditLogger->info("Socio modificato: {$socio->CodiceFiscale}", ['changes' => array_keys($data), 'user' => 'admin']);
        } catch (\Exception $e) {
            $this->auditLogger->error("Errore aggiornamento socio: " . $e->getMessage(), ['cf' => $socio->CodiceFiscale]);
            $response->getBody()->write("Errore aggiornamento: " . $e->getMessage());
            return $response->withStatus(500);
        }

        $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
        return $response
            ->withHeader('Location', $routeParser->urlFor('socio_list'))
            ->withStatus(302);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        try {
            $this->socioRepo->delete($cf);
            $this->auditLogger->info("Socio eliminato: {$cf}", ['user' => 'admin']);
        } catch (\Exception $e) {
            $this->auditLogger->error("Errore eliminazione socio: " . $e->getMessage(), ['cf' => $cf]);
            // Optionally handle error feedback
        }

        $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
        return $response
            ->withHeader('Location', $routeParser->urlFor('socio_list'))
            ->withStatus(302);
    }

    public function exportCsv(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $soci = $this->socioRepo->findAll();

        $csvData = [];
        // Header Row
        $csvData[] = ['Nome', 'Cognome', 'Codice Fiscale', 'Data Nascita', 'Email', 'Telefono', 'Matricola', 'Stato', 'Moroso'];

        foreach ($soci as $socio) {
            $csvData[] = [
                $socio->DatiPersonali->Nome,
                $socio->DatiPersonali->Cognome,
                $socio->CodiceFiscale,
                $socio->DatiPersonali->DataNascita->format('d/m/Y'),
                $socio->DatiPersonali->Email,
                $socio->DatiPersonali->Telefono,
                $socio->Matricola,
                $socio->Stato->name,
                $socio->verificaMorosita() ? 'SI' : 'NO'
            ];
        }

        $stream = fopen('php://memory', 'w+');
        foreach ($csvData as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $response->getBody()->write($csvContent);

        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="soci_export_' . date('Y-m-d') . '.csv"')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }
    public function uploadDocument(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            return $response->withStatus(404);
        }

        $files = $request->getUploadedFiles();
        if (empty($files['documento'])) {
            $response->getBody()->write("Nessun file caricato.");
            return $response->withStatus(400);
        }

        /** @var \Psr\Http\Message\UploadedFileInterface $uploadedFile */
        $uploadedFile = $files['documento'];

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            // Validazione MIME type e dimensione tramite ValidationService
            if (!$this->validator->isValidFileUpload($uploadedFile->getClientMediaType() ?? 'application/octet-stream', $uploadedFile->getSize() ?? 0)) {
                $this->auditLogger->error("Tentativo di caricamento file non valido per socio: {$cf}", [
                    'filename' => $uploadedFile->getClientFilename(),
                    'mime' => $uploadedFile->getClientMediaType(),
                    'size' => $uploadedFile->getSize()
                ]);

                $response->getBody()->write("File non valido. Formati supportati: PDF, JPG, PNG, DOC, DOCX. Dimensione massima: 5MB.");
                return $response->withStatus(400);
            }

            $filename = $uploadedFile->getClientFilename();
            // Move file to SECURE storage (outside public)
            $uploadDir = __DIR__ . '/../../storage/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Use a single ID for both storage and entity
            $uniqueId = uniqid();

            // Generate unique name to prevent collision
            $targetPath = $uploadDir . $uniqueId . '_' . $filename;
            $uploadedFile->moveTo($targetPath);

            // Create Document Entity
            $doc = new \FratellanzaMilitare\GestioneSoci\DocumentoGenerico(); // Assuming Generic Document Type
            $doc->IdUnivoco = $uniqueId;
            $doc->NomeFile = $filename;
            $doc->HashSHA256 = hash_file('sha256', $targetPath);
            $doc->Stato = \FratellanzaMilitare\Enum\StatoDocumento::IN_ATTESA;
            $doc->DataCaricamento = new \DateTime();

            // Determine type from form input if available, else default
            // $doc->TipoDocumento = ... (Generic for now)

            $socio->aggiungiDocumento($doc);
            $this->socioRepo->save($socio);

            $this->auditLogger->info("Documento caricato per socio: {$cf}", ['file' => $filename, 'user' => 'admin']);
        }

        return $response
            ->withHeader('Location', \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser()->urlFor('socio_detail', ['cf' => $cf]))
            ->withStatus(302);
    }

    public function downloadDocument(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $docId = $args['id'];

        $socio = $this->socioRepo->findByCodiceFiscale($cf);
        if (!$socio) {
            return $response->withStatus(404);
        }

        $documento = null;
        foreach ($socio->DocumentiAssociati as $doc) {
            if ($doc->IdUnivoco === $docId) {
                $documento = $doc;
                break;
            }
        }

        if (!$documento) {
            $response->getBody()->write("Documento non trovato.");
            return $response->withStatus(404);
        }

        // Try to find file in SECURE uploads directory
        $filePath = __DIR__ . '/../../storage/uploads/' . $documento->IdUnivoco . '_' . $documento->NomeFile;

        // Fallback checks for legacy
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . '/../../storage/uploads/' . $documento->NomeFile;
        }

        if (!file_exists($filePath)) {
            $this->auditLogger->error("File fisico non trovato", ['path' => $filePath, 'doc_id' => $docId]);
            $response->getBody()->write("Errore: Il file richiesto non è più disponibile sul server.");
            return $response->withStatus(404);
        }

        // Efficiency: Use Streaming instead of file_get_contents
        $fileStream = new \Slim\Psr7\Stream(fopen($filePath, 'r'));
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        return $response
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Disposition', 'attachment; filename="' . $documento->NomeFile . '"')
            ->withHeader('Content-Length', (string) filesize($filePath))
            ->withBody($fileStream);
    }

    public function deleteDocument(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cf = $args['cf'];
        $docId = $args['id'];

        $socio = $this->socioRepo->findByCodiceFiscale($cf);
        if (!$socio) {
            return $response->withStatus(404);
        }

        // Find document to get filename for physical deletion
        $documento = null;
        foreach ($socio->DocumentiAssociati as $doc) {
            if ($doc->IdUnivoco === $docId) {
                $documento = $doc;
                break;
            }
        }

        if ($documento) {
            // Remove physical file from SECURE storage
            $filePath = __DIR__ . '/../../storage/uploads/' . $documento->IdUnivoco . '_' . $documento->NomeFile;
            if (file_exists($filePath)) {
                unlink($filePath);
            } else {
                // Try legacy/fallback path
                $fallbackPath = __DIR__ . '/../../storage/uploads/' . $documento->NomeFile;
                if (file_exists($fallbackPath)) {
                    unlink($fallbackPath);
                }
            }

            // Remove from entity
            $socio->rimuoviDocumento($docId);
            $this->socioRepo->save($socio);

            $this->auditLogger->info("Documento eliminato per socio: {$cf}", ['doc_id' => $docId, 'file' => $documento->NomeFile, 'user' => 'admin']);
        }

        return $response
            ->withHeader('Location', \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser()->urlFor('socio_detail', ['cf' => $cf]))
            ->withStatus(302);
    }
}
