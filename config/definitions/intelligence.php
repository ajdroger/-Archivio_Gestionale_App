<?php

use Psr\Container\ContainerInterface;

return [
    \FratellanzaMilitare\Controller\Intelligence\StatsDashboardController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Intelligence\StatsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class),
            $c->get(\FratellanzaMilitare\Debug\ResilienceMonitor::class),
            $c->get(\FratellanzaMilitare\Service\HealthCheckService::class)
        );
    },
    \FratellanzaMilitare\Controller\Intelligence\ReportExportController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Intelligence\ReportExportController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },
];
