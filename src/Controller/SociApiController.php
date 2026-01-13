<?php

declare(strict_types=1);

namespace MCAG\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MCAG\Helper\PaginationHelper;
use MCAG\DTO\PaginationResponse;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Soci",
    description: "Gestione Soci e Anagrafica"
)]
class SociApiController
{
    private PDOSocioRepository $socioRepo;

    public function __construct(PDOSocioRepository $socioRepo)
    {
        $this->socioRepo = $socioRepo;
    }

    #[OA\Get(
        path: "/api/v1/soci",
        tags: ["Soci"],
        summary: "Lista paginata dei soci",
        description: "Restituisce una lista di soci con paginazione opzionale",
        security: [["apiKey" => []]],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Numero pagina",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Elementi per pagina",
                required: false,
                schema: new OA\Schema(type: "integer", default: 50)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista recuperata con successo",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/SocioSummary")),
                        new OA\Property(property: "meta", type: "object", properties: [
                            new OA\Property(property: "total", type: "integer"),
                            new OA\Property(property: "page", type: "integer"),
                            new OA\Property(property: "per_page", type: "integer"),
                            new OA\Property(property: "total_pages", type: "integer")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorizzato"),
            new OA\Response(response: 403, description: "Accesso negato")
        ]
    )]
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
            data: $data,
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

    #[OA\Get(
        path: "/api/v1/soci/{cf}",
        tags: ["Soci"],
        summary: "Dettaglio singolo socio",
        description: "Recupera i dettagli completi di un socio tramite Codice Fiscale",
        security: [["apiKey" => []]],
        parameters: [
            new OA\Parameter(
                name: "cf",
                in: "path",
                description: "Codice Fiscale del socio",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Socio trovato",
                content: new OA\JsonContent(ref: "#/components/schemas/SocioDetail")
            ),
            new OA\Response(response: 404, description: "Socio non trovato")
        ]
    )]
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

    #[OA\Post(
        path: "/api/v1/soci",
        tags: ["Soci"],
        summary: "Crea nuovo socio",
        description: "Crea un nuovo socio nel sistema",
        security: [["apiKey" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["codice_fiscale", "nome", "cognome"],
                properties: [
                    new OA\Property(property: "codice_fiscale", type: "string", example: "RSSMRA80A01H501U"),
                    new OA\Property(property: "nome", type: "string", example: "Mario"),
                    new OA\Property(property: "cognome", type: "string", example: "Rossi"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "mario.rossi@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Socio creato con successo",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Socio creato con successo"),
                        new OA\Property(property: "data", ref: "#/components/schemas/SocioDetail")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Dati mancanti o invalidi"),
            new OA\Response(response: 500, description: "Errore interno")
        ]
    )]
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

#[OA\Schema(
    schema: "SocioSummary",
    type: "object",
    properties: [
        new OA\Property(property: "cf", type: "string", example: "RSSMRA80A01H501U"),
        new OA\Property(property: "nome", type: "string", example: "Mario"),
        new OA\Property(property: "cognome", type: "string", example: "Rossi"),
        new OA\Property(property: "email", type: "string", format: "email"),
        new OA\Property(property: "stato", type: "string", example: "Attivo")
    ]
)]
class SocioSummarySchema
{
}

#[OA\Schema(
    schema: "SocioDetail",
    allOf: [new OA\Schema(ref: "#/components/schemas/SocioSummary")],
    properties: [
        new OA\Property(property: "data_nascita", type: "string", format: "date", example: "1980-01-01"),
        new OA\Property(property: "telefono", type: "string", example: "+39 333 1234567"),
        new OA\Property(property: "indirizzo", type: "string", example: "Via Roma 1"),
        new OA\Property(property: "matricola", type: "string", example: "2024-001")
    ]
)]
class SocioDetailSchema
{
}


