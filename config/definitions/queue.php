<?php

use MCAG\Queue\QueueInterface;
use MCAG\Queue\DatabaseQueue;
use Psr\Container\ContainerInterface;

return [
    QueueInterface::class => function (ContainerInterface $c) {
        $pdo = $c->get(\PDO::class);
        return new DatabaseQueue($pdo);
    },
];


