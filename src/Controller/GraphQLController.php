<?php
declare(strict_types=1);

namespace FratellanzaMilitare\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GraphQL\GraphQL;
use GraphQL\Error\DebugFlag;
use FratellanzaMilitare\GraphQL\Schema;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

class GraphQLController
{
    public function __construct(
        private PDOSocioRepository $repo
    ) {
    }

    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $schema = Schema::build($this->repo);

            $input = json_decode((string) $request->getBody(), true);
            $query = $input['query'] ?? null;
            $variableValues = $input['variables'] ?? null;

            if ($query === null) {
                $result = ['errors' => [['message' => 'Query is missing']]];
            } else {
                $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues)
                    ->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
            }
        } catch (\Throwable $e) {
            $result = [
                'errors' => [
                    ['message' => $e->getMessage()]
                ]
            ];
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
