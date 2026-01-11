<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    // ===== NEW SERVICES - Q1 & Q2 =====

    \FratellanzaMilitare\Service\RedisService::class => function () {
        return new \FratellanzaMilitare\Service\RedisService();
    },

    \FratellanzaMilitare\Service\CacheService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\CacheService(
            $c->get(\FratellanzaMilitare\Service\RedisService::class)
        );
    },

    \FratellanzaMilitare\Service\QueueService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\QueueService(
            $c->get(PDO::class)
        );
    },

    \FratellanzaMilitare\Service\BackupVerificationService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\BackupVerificationService(
            $c->get(PDO::class),
            __DIR__ . '/../../storage/backups'
        );
    },

    \FratellanzaMilitare\Service\HealthCheckService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\HealthCheckService(
            $c->get(PDO::class),
            $c->get(\FratellanzaMilitare\Service\RedisService::class),
            $c->get(\FratellanzaMilitare\Service\QueueService::class),
            __DIR__ . '/../../storage'
        );
    },

    \FratellanzaMilitare\Service\JsonLogFormatter::class => function () {
        return new \FratellanzaMilitare\Service\JsonLogFormatter('FratellanzaMilitare');
    },

    // ===== EXISTING SERVICES =====
    \FratellanzaMilitare\Service\ValidationService::class => function () {
        return new \FratellanzaMilitare\Service\ValidationService();
    },

    \FratellanzaMilitare\Service\BackupService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\BackupService(
            __DIR__ . '/../../database.sqlite',
            __DIR__ . '/../../storage/backups',
            $c->get(LoggerInterface::class),
            14
        );
    },

    \FratellanzaMilitare\Debug\ResilienceMonitor::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Debug\ResilienceMonitor(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class),
            __DIR__ . '/../../storage'
        );
    },

    \FratellanzaMilitare\Service\EmailServiceInterface::class => function (ContainerInterface $c) {
        $config = [
            'host' => $_ENV['SMTP_HOST'] ?? 'smtp.example.com',
            'username' => $_ENV['SMTP_USER'] ?? 'user@example.com',
            'password' => $_ENV['SMTP_PASS'] ?? 'secret',
            'port' => $_ENV['SMTP_PORT'] ?? 587
        ];
        return new \FratellanzaMilitare\Service\SmtpEmailService($c->get(LoggerInterface::class), $config);
    },

    \FratellanzaMilitare\Service\PdfGenerationService::class => function () {
        return new \FratellanzaMilitare\Service\PdfGenerationService();
    },

    \FratellanzaMilitare\Service\RegistrationService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\RegistrationService(
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class),
            $c->get(\FratellanzaMilitare\Service\ValidationService::class),
            $c->get(\FratellanzaMilitare\Service\PdfGenerationService::class),
            $c->get(\FratellanzaMilitare\Service\EmailServiceInterface::class),
            $c->get(LoggerInterface::class)
        );
    },

    \FratellanzaMilitare\Service\Demo\DemoInvitationService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\Demo\DemoInvitationService(
            $c->get(\FratellanzaMilitare\Service\EmailServiceInterface::class),
            $c->get(LoggerInterface::class),
            $_ENV['APP_URL'] ?? 'http://localhost/fratellanza-militare-archivio/public',
            $c->get(PDO::class)
        );
    },
];
