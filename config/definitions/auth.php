<?php

use Psr\Container\ContainerInterface;

return [
    \MCAG\Controller\Auth\LoginFlowController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Auth\LoginFlowController(
            $c->get(Mustache_Engine::class),
            new \MCAG\Service\InputValidator()
        );
    },
    \MCAG\Controller\Auth\TwoFactorController::class => function (ContainerInterface $c) {
        return new \MCAG\Controller\Auth\TwoFactorController($c->get(Mustache_Engine::class));
    },
    \MCAG\Controller\Auth\LogoutController::class => function () {
        return new \MCAG\Controller\Auth\LogoutController();
    },
];


