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
            $c->get(\MCAG\Service\HealthCheckService::class),
            $c->get(\MCAG\Service\ConfigurationService::class),
            $c->get(\MCAG\SecurityLayer\AuditTrail::class) // Injected
        );
    },

    \MCAG\Controller\Admin\DashboardActionController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Admin\DashboardActionController(
            $c->get(\Psr\Log\LoggerInterface::class),
            $c->get(\MCAG\Service\ConfigurationService::class)
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

    \MCAG\Controller\WarfareController::class => function (Psr\Container\ContainerInterface $c) {
        return new \MCAG\Controller\WarfareController(
            new \MCAG\SecurityLayer\Arsenal\FirewallOps(__DIR__ . '/../../'), // Project Root
            new \MCAG\SecurityLayer\Arsenal\IntelProbe(),
            new \MCAG\SecurityLayer\Arsenal\Tarpit(),
            $c->get(\MCAG\SecurityLayer\AuditTrail::class)
        );
    },
];


