<?php

use Psr\Container\ContainerInterface;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;

return [
    \MCAG\Controller\Anagrafica\Soci\ListController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Anagrafica\Soci\ListController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class)
        );
    },
    \MCAG\Controller\Anagrafica\Soci\DetailController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Anagrafica\Soci\DetailController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger')
        );
    },
    \MCAG\Controller\Anagrafica\Soci\PersistenceController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Anagrafica\Soci\PersistenceController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger'),
            $c->get(\MCAG\Service\ValidationService::class),
            $c->get(\MCAG\Service\RegistrationService::class)
        );
    },
    \MCAG\Controller\Anagrafica\Soci\ActionController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Anagrafica\Soci\ActionController($c->get('audit_logger'));
    },
    \MCAG\Controller\Anagrafica\Documenti\StorageController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Anagrafica\Documenti\StorageController(
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger'),
            $c->get(\MCAG\Service\ValidationService::class)
        );
    },
    \MCAG\Controller\Anagrafica\Servizi\SocioExportController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Anagrafica\Servizi\SocioExportController(
            $c->get(PDOSocioRepository::class),
            $c->get(Mustache_Engine::class)
        );
    },
];


