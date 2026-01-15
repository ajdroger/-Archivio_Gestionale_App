<?php

namespace MCAG\Controller\Anagrafica\Documenti;

use MCAG\Enum\StatoDocumento;
use MCAG\GestioneSoci\DocumentoGenerico;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Stream;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla gestione fisica e logica dei documenti soci.
 */
/**
 * Controller per la gestione documentale dei soci.
 * 
 * Gestisce upload, download ed eliminazione di documenti (PDF, immagini, DOC)
 * associandoli ai singoli soci. Integra validazione MIME/Size e Audit Log.
 */
class StorageController
{
    private PDOSocioRepository $socioRepo;
    private LoggerInterface $auditLogger;
    private ValidationService $validator;

    public function __construct(PDOSocioRepository $socioRepo, LoggerInterface $auditLogger, ValidationService $validator)
    {
        $this->socioRepo = $socioRepo;
        $this->auditLogger = $auditLogger;
        $this->validator = $validator;
    }

    /**
     * Gestisce l'upload di un documento per un socio.
     * 
     * Valida il file (dimensione, tipo), lo salva con un nome univoco (ID + filename originale)
     * e crea un record DocumentoGenerico associato al socio.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function upload(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        if (!$socio)
            return $response->withStatus(404);

        $uploadedFile = $request->getUploadedFiles()['documento'] ?? null;
        if ($uploadedFile && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            if (!$this->validator->isValidFileUpload($uploadedFile->getClientMediaType(), $uploadedFile->getSize())) {
                $response->getBody()->write("File non valido (max 5MB, PDF/Immagini/Doc).");
                return $response->withStatus(400);
            }

            // Security: Get Temp Path for advanced validation
            $stream = $uploadedFile->getStream();
            $tmpPath = $stream->getMetadata('uri');

            if ($tmpPath && file_exists($tmpPath)) {
                // 1. Magic Bytes Check (Real MIME)
                if (!$this->validator->validateRealMimeType($tmpPath)) {
                    $response->getBody()->write("File type non valido (controllo integrità fallito).");
                    return $response->withStatus(400);
                }

                // 2. Malware Scan (ClamAV)
                if (!$this->validator->scanForMalware($tmpPath)) {
                    $response->getBody()->write("File potenzialmente malevolo rilevato.");
                    return $response->withStatus(400);
                }
            } else {
                // Fallback if unable to get tmp path (e.g. memory stream)
                $this->auditLogger->warning("Impossibile validare magic bytes per upload: " . $uploadedFile->getClientFilename());
            }

            $uniqueId = uniqid();
            $filename = $uploadedFile->getClientFilename();
            $targetPath = __DIR__ . '/../../../../storage/uploads/' . $uniqueId . '_' . $filename;

            if (!is_dir(dirname($targetPath)))
                mkdir(dirname($targetPath), 0777, true);
            $uploadedFile->moveTo($targetPath);

            $doc = new DocumentoGenerico();
            $doc->IdUnivoco = $uniqueId;
            $doc->NomeFile = $filename;
            $doc->HashSHA256 = hash_file('sha256', $targetPath);
            $doc->Stato = StatoDocumento::IN_ATTESA;
            $doc->DataCaricamento = new \DateTime();

            // Capture specific document type from form (default to GENERICO)
            $params = $request->getParsedBody();
            $doc->TipoDocumento = $params['tipo_documento'] ?? 'GENERICO';

            $socio->aggiungiDocumento($doc);
            $this->socioRepo->save($socio);
            $this->auditLogger->info("Documento caricato per socio: {$args['cf']}");
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_detail', ['cf' => $args['cf']]))->withStatus(302);
    }

    /**
     * Scarica un documento archiviato.
     * 
     * Verifica l'appartenenza del documento al socio e la sua esistenza su disco.
     * Serve il file con i corretti header per il download.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function download(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        $documento = null;
        foreach ($socio->DocumentiAssociati as $doc) {
            if ($doc->IdUnivoco === $args['id']) {
                $documento = $doc;
                break;
            }
        }

        if (!$documento)
            return $response->withStatus(404);

        $filePath = __DIR__ . '/../../../../storage/uploads/' . $documento->IdUnivoco . '_' . $documento->NomeFile;
        if (!file_exists($filePath))
            return $response->withStatus(404);

        $fileStream = new Stream(fopen($filePath, 'r'));
        return $response->withHeader('Content-Type', mime_content_type($filePath))
            ->withHeader('Content-Disposition', 'attachment; filename="' . $documento->NomeFile . '"')
            ->withBody($fileStream);
    }

    /**
     * Elimina logicamente un documento dal socio.
     * 
     * NOTA: Attualmente non cancella il file fisico per policy di sicurezza/auditing,
     * ma disassocia il record dal socio.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $socio = $this->socioRepo->findByCodiceFiscale($args['cf']);
        $socio->rimuoviDocumento($args['id']);
        $this->socioRepo->save($socio);
        $this->auditLogger->info("Documento eliminato per socio: {$args['cf']}");

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_detail', ['cf' => $args['cf']]))->withStatus(302);
    }
}


