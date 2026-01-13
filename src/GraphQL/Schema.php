<?php
declare(strict_types=1);

namespace MCAG\GraphQL;

use GraphQL\Type\Schema as GraphQLSchema;
use MCAG\GraphQL\Types\QueryType;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;

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


