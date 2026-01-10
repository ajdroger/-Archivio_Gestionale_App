<?php

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\Service\HealthCheckService;
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: "/health",
        tags: ["System"],
        summary: "System Health Check",
        description: "Verifica lo stato di Database, FileSystem e Servizi Essenziali",
        responses: [
            new OA\Response(
                response: 200,
                description: "Sistema operativo (Healthy)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "healthy"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time"),
                        new OA\Property(property: "checks", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: "Sistema degradato o non disponibile (Unhealthy)"
            )
        ]
    )]
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
