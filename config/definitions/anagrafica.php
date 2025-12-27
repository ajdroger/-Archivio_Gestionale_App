<?php

use Psr\Container\ContainerInterface;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

return [
    \FratellanzaMilitare\Controller\Anagrafica\Soci\ListController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Anagrafica\Soci\ListController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class)
        );
    },
    \FratellanzaMilitare\Controller\Anagrafica\Soci\DetailController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Anagrafica\Soci\DetailController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger')
        );
    },
    \FratellanzaMilitare\Controller\Anagrafica\Soci\PersistenceController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Anagrafica\Soci\PersistenceController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger'),
            $c->get(\FratellanzaMilitare\Service\ValidationService::class),
            $c->get(\FratellanzaMilitare\Service\RegistrationService::class)
        );
    },
    \FratellanzaMilitare\Controller\Anagrafica\Soci\ActionController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Anagrafica\Soci\ActionController($c->get('audit_logger'));
    },
    \FratellanzaMilitare\Controller\Anagrafica\Documenti\StorageController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Anagrafica\Documenti\StorageController(
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger'),
            $c->get(\FratellanzaMilitare\Service\ValidationService::class)
        );
    },
    \FratellanzaMilitare\Controller\Anagrafica\Servizi\SocioExportController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Anagrafica\Servizi\SocioExportController($c->get(PDOSocioRepository::class));
    },
];
