<?php

use Psr\Container\ContainerInterface;

return [
    \FratellanzaMilitare\Controller\SettingsController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\SettingsController($c->get(Mustache_Engine::class));
    },

    \FratellanzaMilitare\Controller\HomeController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\HomeController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsSystemController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsSystemController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\Debug\ResilienceMonitor::class),
            $c->get(PDO::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsAuditController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsAuditController(
            $c->get(Mustache_Engine::class),
            $c->get(PDO::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\Controller\DevTools\DevToolsSystemController::class),
            $c->get(\FratellanzaMilitare\Controller\DevTools\DevToolsAuditController::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController::class => function () {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController();
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController(
            $c->get(Mustache_Engine::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController::class => function () {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController();
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsScriptController::class => function () {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsScriptController();
    },
];
