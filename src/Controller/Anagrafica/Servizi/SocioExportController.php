<?php

namespace FratellanzaMilitare\Controller\Anagrafica\Servizi;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller dedicato alle esportazioni di massa dei dati soci.
 */
/**
 * Controller per l'esportazione dati dei Soci.
 * 
 * Fornisce funzionalità per esportare l'elenco soci in formati portabili (CSV).
 */
class SocioExportController
{
    private PDOSocioRepository $socioRepo;
    private \Mustache_Engine $mustache;

    public function __construct(PDOSocioRepository $socioRepo, \Mustache_Engine $mustache)
    {
        $this->socioRepo = $socioRepo;
        $this->mustache = $mustache;
    }

    /**
     * Esporta l'elenco completo dei soci in formato CSV.
     * 
     * Genera un file CSV al volo utilizzando uno stream di memoria.
     * Include header e colonne principali (Nome, Cognome, CF, Stato, Morosità).
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface File download (text/csv)
     */
    public function exportCsv(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
