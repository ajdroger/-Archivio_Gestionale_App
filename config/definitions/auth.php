<?php

use Psr\Container\ContainerInterface;

return [
    \FratellanzaMilitare\Controller\Auth\LoginFlowController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Auth\LoginFlowController($c->get(Mustache_Engine::class));
    },
    \FratellanzaMilitare\Controller\Auth\TwoFactorController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\Auth\TwoFactorController($c->get(Mustache_Engine::class));
    },
    \FratellanzaMilitare\Controller\Auth\LogoutController::class => function () {
        return new \FratellanzaMilitare\Controller\Auth\LogoutController();
    },
];
