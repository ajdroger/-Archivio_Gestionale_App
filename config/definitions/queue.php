<?php

use FratellanzaMilitare\Queue\QueueInterface;
use FratellanzaMilitare\Queue\DatabaseQueue;
use Psr\Container\ContainerInterface;

return [
    QueueInterface::class => function (ContainerInterface $c) {
        $pdo = $c->get(\PDO::class);
        return new DatabaseQueue($pdo);
    },
];
