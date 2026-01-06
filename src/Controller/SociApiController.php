<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\Helper\PaginationHelper;
use FratellanzaMilitare\DTO\PaginationResponse;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="Fratellanza Militare API", version="1.0.0")
 */
class SociApiController
{
    private PDOSocioRepository $socioRepo;

    public function __construct(PDOSocioRepository $socioRepo)
    {
        $this->socioRepo = $socioRepo;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/soci",
     *     @OA\Response(response="200", description="Lista dei soci")
     * )
     */
    public function list(Request $request, Response $response): Response
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $perPage = (int) ($request->getQueryParams()['per_page'] ?? 50);

        // Count total
        $total = $this->socioRepo->count();

        // Get paginated data
        $soci = $this->socioRepo->findAllPaginated($page, $perPage);

        // Convert to array
        $data = array_map(function ($socio) {
            return [
                'cf' => $socio->CodiceFiscale,
                'nome' => $socio->DatiPersonali->Nome,
                'cognome' => $socio->DatiPersonali->Cognome,
                'email' => $socio->DatiPersonali->Email,
                'stato' => $socio->Stato->name
            ];
        }, $soci);

        $pagination = PaginationHelper::paginate($total, $page, $perPage);

        $paginatedResponse = new PaginationResponse(
            data: $data, // Use the mapped data, not raw entity objects
            total: $total,
            page: $page,
            perPage: $perPage,
            totalPages: $pagination['total_pages'],
            hasPrevious: $pagination['has_previous'],
            hasNext: $pagination['has_next']
        );

        $response->getBody()->write(json_encode($paginatedResponse->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/soci/{cf}",
     *     @OA\Response(response="200", description="Dettaglio socio")
     * )
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $cf = $args['cf'];
        $socio = $this->socioRepo->findByCodiceFiscale($cf);

        if (!$socio) {
            $error = json_encode(['error' => 'Socio non trovato'], JSON_PRETTY_PRINT);
            $response->getBody()->write($error);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = [
            'cf' => $socio->CodiceFiscale,
            'nome' => $socio->DatiPersonali->Nome,
            'cognome' => $socio->DatiPersonali->Cognome,
            'data_nascita' => $socio->DatiPersonali->DataNascita->format('Y-m-d'),
            'email' => $socio->DatiPersonali->Email,
            'telefono' => $socio->DatiPersonali->Telefono,
            'indirizzo' => $socio->DatiPersonali->Indirizzo,
            'stato' => $socio->Stato->name,
            'matricola' => $socio->Matricola
        ];

        $payload = json_encode($data, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/soci",
     *     @OA\Response(response="201", description="Socio creato")
     * )
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validazione base
        if (empty($data['codice_fiscale']) || empty($data['nome']) || empty($data['cognome'])) {
            $error = json_encode(['error' => 'Dati mancanti (codice_fiscale, nome, cognome richiesti)'], JSON_PRETTY_PRINT);
            $response->getBody()->write($error);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Qui si dovrebbe usare il Repository o un Service per la creazione
            // $this->socioRepo->save($newSocio);

            // Mock response per ora
            $payload = json_encode(['message' => 'Socio creato con successo (MOCK)', 'data' => $data], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $error = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            $response->getBody()->write($error);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
