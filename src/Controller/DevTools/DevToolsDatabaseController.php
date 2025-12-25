<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\SecurityLayer\AuditTrail;

/**
 * DevTools Database Controller
 * 
 * Handles database queries and audit log exports
 */
class DevToolsDatabaseController
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function dbQuery(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $sql = $data['sql'] ?? '';

        try {
            $pdo = DatabaseConnection::getConnection();
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

    public function exportAuditPdf(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $db = DatabaseConnection::getConnection();
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

    public function exportAuditExcel(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $db = DatabaseConnection::getConnection();
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
