<?php
declare(strict_types=1);

namespace MCAG\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;

class QueryType extends ObjectType
{
    public function __construct(PDOSocioRepository $repo)
    {
        $socioType = new SocioType();

        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'socio' => [
                    'type' => $socioType,
                    'description' => 'Recupera un singolo socio per CF',
                    'args' => [
                        'codiceFiscale' => Type::nonNull(Type::string())
                    ],
                    'resolve' => function ($root, $args) use ($repo) {
                        return $repo->findByCodiceFiscale($args['codiceFiscale']);
                    }
                ],
                'soci' => [
                    'type' => Type::listOf($socioType),
                    'description' => 'Lista paginata di soci',
                    'args' => [
                        'page' => ['type' => Type::int(), 'defaultValue' => 1],
                        'perPage' => ['type' => Type::int(), 'defaultValue' => 50]
                    ],
                    'resolve' => function ($root, $args) use ($repo) {
                        return $repo->findAllPaginated($args['page'], $args['perPage']);
                    }
                ]
            ]
        ]);
    }
}


