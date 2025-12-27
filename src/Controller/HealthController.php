<?php

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\Service\HealthCheckService;

/**
 * Health Check Controller - Enhanced with comprehensive checks
 */
final class HealthController
{
    private HealthCheckService $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    public function check(Request $request, Response $response): Response
    {
        $result = $this->healthCheckService->checkAll();

        // HTTP status code based on overall health
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;

        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
