<?php

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\Service\HealthCheckService;

/**
 * Health Check Controller - Enhanced with comprehensive checks
 */
/**
 * Controller per il monitoraggio dello stato di salute del sistema.
 * 
 * Esegue una serie di controlli diagnostici (database, disco, servizi)
 * per determinare se l'applicazione è operativa e funzionante correttamente.
 */
final class HealthController
{
    private HealthCheckService $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Esegue il check completo di salute.
     * 
     * Restituisce un JSON con lo stato di ogni servizio monitorato.
     * Se il sistema è 'healthy', ritorna 200 OK, altrimenti 503 Service Unavailable.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
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
