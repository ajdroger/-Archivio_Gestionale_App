<?php

namespace FratellanzaMilitare\Controller\Intelligence;

use FratellanzaMilitare\GestioneSoci\SocioRepository;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Dompdf\Dompdf;

/**
 * Controller dedicato alla generazione ed esportazione di report (PDF/Excel).
 */
/**
 * Controller per la generazione di reportistica avanzata.
 * 
 * Gestisce l'esportazione in PDF (tramite Dompdf) ed Excel/CSV
 * delle statistiche e delle liste soci filtrate.
 */
class ReportExportController
{
    private Mustache_Engine $mustache;
    private SocioRepository $repository;

    public function __construct(Mustache_Engine $mustache, SocioRepository $repository)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;
    }

    /**
     * Genera un report PDF completo.
     * 
     * Raccoglie statistiche e lista soci (filtrata opzionalmente),
     * renderizza una vista HTML specifica ('report_pdf') e la converte in PDF.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface File download (application/pdf)
     */
    public function exportPdf(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = [];
        if (!empty($params['status']))
            $filters['stato'] = $params['status'];
        if (!empty($params['payment_status']))
            $filters['moroso'] = ($params['payment_status'] === 'moroso');

        $soci = empty($filters) ? $this->repository->findAll() : $this->repository->findByFilters($filters);
        $stats = $this->repository->getStatistics();

        $html = $this->mustache->render('report_pdf', [
            'type_soci' => true,
            'stats' => $stats,
            'soci' => $soci,
            'year' => date('Y')
        ]);

        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $response->getBody()->write($dompdf->output());
        return $response->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="Report_Soci_' . date('Y-m-d') . '.pdf"');
    }

    /**
     * Esporta i dati in formato CSV/Excel.
     * 
     * Gestisce filtri opzionali per esportare sottoinsiemi di dati.
     * Utilizza UTF-8 BOM per compatibilità con Excel.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface File download (text/csv)
     */
    public function exportExcel(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $output = fopen('php://memory', 'r+');
        fputs($output, "\xEF\xBB\xBF");

        $filters = [];
        if (!empty($params['status']))
            $filters['stato'] = $params['status'];
        if (!empty($params['payment_status']))
            $filters['moroso'] = ($params['payment_status'] === 'moroso');

        $soci = empty($filters) ? $this->repository->findAll() : $this->repository->findByFilters($filters);

        fputcsv($output, ['CF', 'Matricola', 'Cognome', 'Nome', 'Stato', 'Pagamento']);
        foreach ($soci as $s) {
            fputcsv($output, [$s->CodiceFiscale, $s->Matricola, $s->DatiPersonali->Cognome, $s->DatiPersonali->Nome, $s->Stato->name, $s->verificaMorosita() ? 'MOROSO' : 'REGOLARE']);
        }

        rewind($output);
        $response->getBody()->write(stream_get_contents($output));
        fclose($output);

        return $response->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="Export_Soci_' . date('Y-m-d') . '.csv"');
    }
}
