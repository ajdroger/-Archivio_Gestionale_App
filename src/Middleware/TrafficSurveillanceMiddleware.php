<?php

namespace MCAG\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use PDO;

class TrafficSurveillanceMiddleware
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $start = microtime(true);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // 1. Analyze Threat Vectors (Pre-Execution)
        $threatScore = 0;
        $riskLevel = 'LOW';
        $details = [];

        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        $userAgent = $request->getHeaderLine('User-Agent');
        $queryParams = $request->getQueryParams();

        // 1.1 SQL Injection Heuristics
        $sqliPatterns = ['UNION SELECT', 'OR 1=1', '--', 'DROP TABLE', 'SLEEP(', 'waitfor delay'];
        foreach ($queryParams as $val) {
            foreach ($sqliPatterns as $pattern) {
                if (stripos($val, $pattern) !== false) {
                    $threatScore += 50;
                    $riskLevel = 'CRITICAL';
                    $details[] = 'SQLI_ATTEMPT';
                }
            }
        }

        // 1.2 XSS Heuristics
        if (strpos(urldecode($request->getUri()->getQuery()), '<script>') !== false) {
            $threatScore += 40;
            $riskLevel = 'HIGH';
            $details[] = 'XSS_ATTEMPT';
        }

        // 1.3 Bot/Scanner Detection
        if (strpos($userAgent, 'curl') !== false || strpos($userAgent, 'python') !== false) {
            $threatScore += 20;
            $details[] = 'BOT_TRAFFIC';
            if ($riskLevel === 'LOW')
                $riskLevel = 'MEDIUM';
        }

        // 1.4 Path Traversal / Admin Probing
        if (strpos($path, '..') !== false || strpos($path, '.env') !== false || strpos($path, 'wp-admin') !== false) {
            $threatScore += 30;
            $riskLevel = 'HIGH';
            $details[] = 'PATH_PROBING';
        }

        // 2. Execute Request
        $response = $handler->handle($request);

        // 3. Post-Execution Analysis
        $statusCode = $response->getStatusCode();
        $executionTime = microtime(true) - $start;

        if ($statusCode === 404 || $statusCode === 403) {
            $threatScore += 10; // Suspicious endpoint probing
            if ($riskLevel === 'LOW')
                $riskLevel = 'Note';
        }

        // 4. Log to Traffic DB
        $this->logTraffic($ip, $method, $path, $statusCode, $userAgent, $executionTime, $riskLevel, $threatScore, $details);

        return $response;
    }

    private function logTraffic($ip, $method, $path, $status, $ua, $time, $risk, $score, $details)
    {
        // [FIX] GHOST TRAFFIC: Exclude internal dashboard polling calls from logging
        if (str_contains($path, '/api/public/security/pulse') || str_contains($path, '/api/public/security/neutralize')) {
            return;
        }

        try {
            // Fast GeoIP (Mock or Cache)
            // Ideally use a service, for now, we use the Dashboard's logic or a placeholder
            $geo = json_encode(['lat' => 0, 'lon' => 0, 'details' => implode(',', $details)]);

            $sql = "INSERT INTO traffic_logs (ip_address, method, path, status_code, user_agent, execution_time, risk_level, threat_score, geodata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$ip, $method, substr($path, 0, 255), $status, substr($ua, 0, 255), $time, $risk, $score, $geo]);
        } catch (\Throwable $e) {
            // Fail silently to not impact user experience
            // Optionally log to file for debug: file_put_contents(__DIR__ . '/../../middleware_error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
}
