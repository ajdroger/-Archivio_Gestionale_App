<?php

use Psr\Container\ContainerInterface;
use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;

return [
        // WorkShift Repository
    PDOWorkshiftRepository::class => function (ContainerInterface $c) {
        return new PDOWorkshiftRepository($c->get(PDO::class));
    },

    // Queue Service (Required for HealthCheck)
    \MCAG\Service\QueueService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\QueueService($c->get(PDO::class));
    },

    // Redis Service (Wrapper)
    \MCAG\Service\RedisService::class => function (ContainerInterface $c) {
        // Try to get the raw Client from core.php if available, else null
        $client = null;
        if ($c->has(\Predis\Client::class)) {
            try {
                $client = $c->get(\Predis\Client::class);
            } catch (\Throwable $e) {
                // Ignore retrieval error, pass null
            }
        }
        return new \MCAG\Service\RedisService($client);
    },

    // HealthCheck Service
    \MCAG\Service\HealthCheckService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\HealthCheckService(
            $c->get(PDO::class),
            $c->get(\MCAG\Service\RedisService::class), // Defined in core.php
            $c->get(\MCAG\Service\QueueService::class)
        );
    },

    // WorkShift Controller
    \MCAG\Controller\External\WorkshiftController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\WorkshiftController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOWorkshiftRepository::class),
            $c->get(\MCAG\Service\HealthCheckService::class)
        );
    },

    // TaskFlow Repository
    \MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository::class => function (ContainerInterface $c) {
        return new \MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository($c->get(PDO::class));
    },

    // TaskFlow Controller
    \MCAG\Controller\External\TaskflowController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\TaskflowController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository::class)
        );
    },

    // ExpenseBar Repository
    \MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository::class => function (ContainerInterface $c) {
        return new \MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository($c->get(PDO::class));
    },

    // ExpenseBar Controller
    \MCAG\Controller\External\ExpensebarController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\ExpensebarController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository::class)
        );
    },

    // WorkShift Info Controller
    \MCAG\Controller\External\WorkshiftInfoController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\WorkshiftInfoController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOWorkshiftRepository::class)
        );
    },
];
