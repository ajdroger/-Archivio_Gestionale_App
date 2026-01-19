<?php

use Psr\Container\ContainerInterface;

return [
    // --- Taskflow ---
    \MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository::class => function (ContainerInterface $c) {
        return new \MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository(
            $c->get(PDO::class)
        );
    },

    \MCAG\Controller\External\TaskflowController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\TaskflowController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository::class)
        );
    },

    // --- Expensebar ---
    \MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository::class => function (ContainerInterface $c) {
        return new \MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository(
            $c->get(PDO::class)
        );
    },

    \MCAG\Controller\External\ExpensebarController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\External\ExpensebarController(
            $c->get(Mustache_Engine::class),
            $c->get(\MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository::class)
        );
    },
];
