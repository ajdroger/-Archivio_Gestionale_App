<?php

use Psr\Container\ContainerInterface;
use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;

return [
        // WorkShift Repository
    PDOWorkshiftRepository::class => function (ContainerInterface $c) {
        return new PDOWorkshiftRepository($c->get(PDO::class));
    },

    // WorkShift Controller
    \MCAG\Controller\External\WorkshiftController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\WorkshiftController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOWorkshiftRepository::class)
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
