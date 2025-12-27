<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Documenti;

use FratellanzaMilitare\Enum\StatoDocumento;
use FratellanzaMilitare\GestioneSoci\DocumentoGenerico;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Stream;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla gestione fisica e logica dei documenti soci.
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

            $socio->aggiungiDocumento($doc);
            $this->socioRepo->save($socio);
            $this->auditLogger->info("Documento caricato per socio: {$args['cf']}");
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('socio_detail', ['cf' => $args['cf']]))->withStatus(302);
    }

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
