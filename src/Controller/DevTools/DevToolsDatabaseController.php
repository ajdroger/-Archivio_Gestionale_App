<?php

namespace MCAG\Controller\DevTools;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use MCAG\SecurityLayer\AuditTrail;

/**
 * DevTools Database Controller
 * 
 * Handles database queries and audit log exports
 */
/**
 * Controller per la gestione diretta del Database.
 * 
 * Offre funzionalità per eseguire query raw (SQL Console) e
 * per esportare dati (Audit Log) in formati PDF o CSV.
 */
class DevToolsDatabaseController
{
    private Mustache_Engine $mustache;
    private ?\PDO $pdo = null;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function setConnection(\PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    private function getConnection(): \PDO
    {
        if ($this->pdo === null) {
            $this->pdo = DatabaseConnection::getConnection();
        }
        return $this->pdo;
    }

    /**
     * Esegue una query SQL arbitraria.
     * 
     * Accetta una stringa SQL in POST e restituisce i risultati o il numero di righe affette.
     * ATTENZIONE: Questo endpoint è potente e richiede permessi amministrativi.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON
     */
    public function dbQuery(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $sql = $data['sql'] ?? '';

        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $results = [];
            if (preg_match('/^\s*SELECT/i', $sql)) {
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $results = [['message' => 'Query eseguita.', 'rows_affected' => $stmt->rowCount()]];
            }

            $response->getBody()->write(json_encode(['results' => $results]));
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Esporta i log di audit in formato PDF.
     * 
     * Utilizza Dompdf per generare un report visualmente curato basato sui filtri correnti.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response Application PDF
     */
    public function exportAuditPdf(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $db = $this->getConnection();
        $auditTrail = AuditTrail::getInstance();
        $auditTrail->setPdo($db);

        $filters = $this->buildFilters($params);
        $result = $auditTrail->ricercaAzioni($filters, 1, -1);
        $logs = $result['data'];

        $html = $this->mustache->render('report_pdf', [
            'type_audit' => true,
            'logs' => $logs,
            'filters' => $params,
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
            ->withHeader('Content-Disposition', 'attachment; filename="Audit_Log_FM_' . date('Y-m-d') . '.pdf"');
    }

    /**
     * Esporta i log di audit in formato CSV.
     * 
     * Genera un file CSV standard per l'importazione in Excel o altri tool.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response Text CSV
     */
    public function exportAuditExcel(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $db = $this->getConnection();
        $auditTrail = AuditTrail::getInstance();
        $auditTrail->setPdo($db);

        $filters = $this->buildFilters($params);
        $result = $auditTrail->ricercaAzioni($filters, 1, -1);
        $logs = $result['data'];

        $output = fopen('php://memory', 'r+');
        fputs($output, "\xEF\xBB\xBF"); // BOM

        fputcsv($output, ['Timestamp', 'User', 'Action', 'Resource', 'IP']);
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['timestamp'],
                $log['username'],
                $log['action'],
                $log['resource_id'],
                $log['ip_address']
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response->getBody()->write($csvContent);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="Audit_Log_FM_' . date('Y-m-d') . '.csv"');
    }

    private function buildFilters(array $params): array
    {
        $filters = [];
        if (!empty($params['start_date'])) {
            $filters['start_date'] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $filters['end_date'] = $params['end_date'];
        }
        if (!empty($params['audit_user'])) {
            $filters['username'] = $params['audit_user'];
        }
        if (!empty($params['resource_id'])) {
            $filters['resource_id'] = $params['resource_id'];
        }
        return $filters;
    }
}


