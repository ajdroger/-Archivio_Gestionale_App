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

    // ===== EVENT BUS ARCHITECTURE (v5.0) =====
    \FratellanzaMilitare\Event\EventBusInterface::class => function (ContainerInterface $c) {
        $bus = new \FratellanzaMilitare\Event\EventBus($c->get(LoggerInterface::class));

        // Register Listeners
        // 1. Log Socio Creation
        $bus->subscribe(
            \FratellanzaMilitare\Event\Events\SocioCreatedEvent::class,
            new \FratellanzaMilitare\Event\Listeners\LogSocioCreationListener($c->get(LoggerInterface::class))
        );

        // 2. AI Indexing (RAG)
        $bus->subscribe(
            \FratellanzaMilitare\Event\Events\SocioCreatedEvent::class,
            $c->get(\FratellanzaMilitare\Event\Listeners\IndexSocioListener::class)
        );

        return $bus;
    },

    // ===== AI SERVICES (v5.0 Phase 3) =====
    \FratellanzaMilitare\AI\Providers\OllamaProvider::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\AI\Providers\OllamaProvider(
            $c->get(LoggerInterface::class)
        );
    },

    \FratellanzaMilitare\AI\RAG\SimpleVectorStore::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\AI\RAG\SimpleVectorStore(
            $c->get(LoggerInterface::class),
            __DIR__ . '/../../storage/ai/index.json'
        );
    },

    \FratellanzaMilitare\Event\Listeners\IndexSocioListener::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Event\Listeners\IndexSocioListener(
            $c->get(LoggerInterface::class),
            $c->get(\FratellanzaMilitare\AI\Providers\OllamaProvider::class),
            $c->get(\FratellanzaMilitare\AI\RAG\SimpleVectorStore::class)
        );
    },
];
