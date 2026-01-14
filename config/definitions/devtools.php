<?php

use Psr\Container\ContainerInterface;

return [
    \MCAG\Controller\SettingsController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\SettingsController($c->get(Mustache_Engine::class));
    },

    \MCAG\Controller\HomeController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\HomeController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\GestioneSoci\SocioRepository::class),
            $c->get(\MCAG\Debug\ResilienceMonitor::class),
            $c->get(\MCAG\Service\HealthCheckService::class)
        );
    },

    \MCAG\Controller\DevTools\DevToolsSystemController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\DevTools\DevToolsSystemController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\Debug\ResilienceMonitor::class),
            $c->get(PDO::class)
        );
    },

    \MCAG\Controller\DevTools\DevToolsAuditController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\DevTools\DevToolsAuditController(
            $c->get(Mustache_Engine::class),
            $c->get(PDO::class)
        );
    },

    \MCAG\Controller\DevTools\DevToolsDashboardController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\DevTools\DevToolsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\Controller\DevTools\DevToolsSystemController::class),
            $c->get(\MCAG\Controller\DevTools\DevToolsAuditController::class),
            $c->get(\MCAG\Service\Demo\DemoInvitationService::class)
        );
    },

    \MCAG\Controller\DevTools\DevToolsFileSystemController::class => function () {
        return new \MCAG\Controller\DevTools\DevToolsFileSystemController();
    },

    \MCAG\Controller\DevTools\DevToolsDatabaseController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\DevTools\DevToolsDatabaseController(
            $c->get(Mustache_Engine::class)
        );
    },

    \MCAG\Controller\DevTools\DevToolsSecurityController::class => function () {
        return new \MCAG\Controller\DevTools\DevToolsSecurityController();
    },
];


