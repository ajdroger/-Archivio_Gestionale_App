<?php

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class HealthController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function check(Request $request, Response $response): Response
    {
        $status = 'healthy';
        $checks = [];
        $statusCode = 200;

        // 1. Database Check
        try {
            $this->db->query("SELECT 1");
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $status = 'degraded';
            $checks['database'] = 'fail: ' . $e->getMessage();
            $statusCode = 503;
        }

        // 2. Disk Space
        $free = disk_free_space(__DIR__);
        if ($free < 100 * 1024 * 1024) { // < 100MB
            $status = 'degraded';
            $checks['disk_space'] = 'low';
        } else {
            $checks['disk_space'] = 'ok';
        }

        // 3. Writable Storage
        if (!is_writable(__DIR__ . '/../../../storage')) {
            $status = 'degraded';
            $checks['storage_permissions'] = 'fail';
        } else {
            $checks['storage_permissions'] = 'ok';
        }

        $payload = [
            'status' => $status,
            'timestamp' => date('c'),
            'checks' => $checks,
            'version' => '1.3.1'
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}
