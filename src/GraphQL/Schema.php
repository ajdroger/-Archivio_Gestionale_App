<?php
declare(strict_types=1);

namespace FratellanzaMilitare\GraphQL;

use GraphQL\Type\Schema as GraphQLSchema;
use FratellanzaMilitare\GraphQL\Types\QueryType;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

class Schema
{
    public static function build(PDOSocioRepository $repo): GraphQLSchema
    {
        $queryType = new QueryType($repo);

        return new GraphQLSchema([
            'query' => $queryType
        ]);
    }
}
