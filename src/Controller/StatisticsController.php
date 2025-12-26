<?php

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mustache_Engine;
use FratellanzaMilitare\GestioneSoci\SocioRepository;

class StatisticsController
{
    private Mustache_Engine $mustache;
    private SocioRepository $repository;

    public function __construct(Mustache_Engine $mustache, SocioRepository $repository)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();

        // Statistics & Filters (Soci)
        $sociFilters = [];
        if (!empty($params['status'])) {
            $sociFilters['stato'] = $params['status'];
        }
        if (!empty($params['payment_status'])) {
            $sociFilters['moroso'] = ($params['payment_status'] === 'moroso');
        }

        // Caching Implementation
        $cacheFile = __DIR__ . '/../../../var/cache/stats_cache.json';
        $cacheTime = 300; // 5 minutes

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            $stats = json_decode(file_get_contents($cacheFile), true);
        } else {
            $stats = $this->repository->getStatistics();
            // Ensure var/cache directory exists
            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0777, true);
            }
            file_put_contents($cacheFile, json_encode($stats));
        }
        $filteredSoci = $this->repository->findByFilters($sociFilters); // For list view if implemented

        $html = $this->mustache->render('statistics', [
            'stats' => $stats,
            'filtered_soci' => $filteredSoci,
            'filtered_count' => count($filteredSoci),
            'filters' => $params,
            'title' => 'Reporting Center',
            'year' => date('Y'),
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function exportPdf(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();

        // Soci Report
        $filters = [];
        if (!empty($params['status'])) {
            $filters['stato'] = $params['status'];
        }
        if (!empty($params['payment_status'])) {
            $filters['moroso'] = ($params['payment_status'] === 'moroso');
        }

        if (empty($filters)) {
            $soci = $this->repository->findAll();
        } else {
            $soci = $this->repository->findByFilters($filters);
        }

        $stats = $this->repository->getStatistics(); // Summary stats (could be recalculated based on filter if needed)

        $html = $this->mustache->render('report_pdf', [
            'type_soci' => true,
            'stats' => $stats,
            'soci' => $soci,
            'year' => date('Y')
        ]);

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $output = $dompdf->output();
        $response->getBody()->write($output);

        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="Report_Soci_' . date('Y-m-d') . '.pdf"');
    }

    public function exportExcel(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();

        $output = fopen('php://memory', 'r+');
        fputs($output, "\xEF\xBB\xBF"); // BOM

        $filters = [];
        if (!empty($params['status'])) {
            $filters['stato'] = $params['status'];
        }
        if (!empty($params['payment_status'])) {
            $filters['moroso'] = ($params['payment_status'] === 'moroso');
        }

        $soci = empty($filters) ? $this->repository->findAll() : $this->repository->findByFilters($filters);

        fputcsv($output, ['Codice Fiscale', 'Matricola', 'Cognome', 'Nome', 'Data Nascita', 'Email', 'Telefono', 'Stato', 'Stato Pagamento']);
        foreach ($soci as $socio) {
            fputcsv($output, [
                $socio->CodiceFiscale,
                $socio->Matricola,
                $socio->DatiPersonali->Cognome,
                $socio->DatiPersonali->Nome,
                $socio->DatiPersonali->DataNascita->format('d/m/Y'),
                $socio->DatiPersonali->Email,
                $socio->DatiPersonali->Telefono,
                $socio->Stato->name,
                $socio->verificaMorosita() ? 'MOROSO' : 'IN REGOLA'
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response->getBody()->write($csvContent);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="Export_Soci_' . date('Y-m-d') . '.csv"');
    }
}
