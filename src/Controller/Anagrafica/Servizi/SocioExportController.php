<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Servizi;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller dedicato alle esportazioni di massa dei dati soci.
 */
class SocioExportController
{
    private PDOSocioRepository $socioRepo;

    public function __construct(PDOSocioRepository $socioRepo)
    {
        $this->socioRepo = $socioRepo;
    }

    public function exportCsv(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $soci = $this->socioRepo->findAll();
        $stream = fopen('php://memory', 'w+');
        fputcsv($stream, ['Nome', 'Cognome', 'CF', 'Data Nascita', 'Email', 'Telefono', 'Matricola', 'Stato', 'Moroso']);

        foreach ($soci as $s) {
            fputcsv($stream, [
                $s->DatiPersonali->Nome,
                $s->DatiPersonali->Cognome,
                $s->CodiceFiscale,
                $s->DatiPersonali->DataNascita->format('d/m/Y'),
                $s->DatiPersonali->Email,
                $s->DatiPersonali->Telefono,
                $s->Matricola,
                $s->Stato->name,
                $s->verificaMorosita() ? 'SI' : 'NO'
            ]);
        }

        rewind($stream);
        $response->getBody()->write(stream_get_contents($stream));
        fclose($stream);

        return $response->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="soci_fm_' . date('Y-m-d') . '.csv"');
    }
}
