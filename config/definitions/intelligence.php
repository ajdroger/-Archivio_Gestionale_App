<?php

use Psr\Container\ContainerInterface;

return [
    \FratellanzaMilitare\Controller\Intelligence\StatsDashboardController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Intelligence\StatsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },
    \FratellanzaMilitare\Controller\Intelligence\ReportExportController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Intelligence\ReportExportController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },
];
