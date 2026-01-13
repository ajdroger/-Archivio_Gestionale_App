<?php
declare(strict_types=1);

namespace MCAG\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class SocioType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Socio',
            'description' => 'Un socio della MCAG',
            'fields' => [
                'codiceFiscale' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Codice Fiscale (Primary Key)'
                ],
                'matricola' => [
                    'type' => Type::string(),
                    'description' => 'Numero matricola (YYYY/SEQ/XXXX)'
                ],
                'nome' => [
                    'type' => Type::nonNull(Type::string())
                ],
                'cognome' => [
                    'type' => Type::nonNull(Type::string())
                ],
                'email' => [
                    'type' => Type::string()
                ],
                'telefono' => [
                    'type' => Type::string()
                ],
                'statoIscrizione' => [
                    'type' => Type::string(),
                    'description' => 'ATTIVO, SOSPESO, CANCELLATO'
                ],
                'dataIscrizione' => [
                    'type' => Type::string(),
                    'resolve' => fn($socio) => method_exists($socio, 'getDataIscrizione') && $socio->getDataIscrizione() ? $socio->getDataIscrizione()->format('Y-m-d') : null
                ],
                'indirizzo' => [
                    'type' => Type::string()
                ]
            ]
        ]);
    }
}


