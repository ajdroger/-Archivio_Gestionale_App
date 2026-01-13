<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    // ===== NEW SERVICES - Q1 & Q2 =====

    \MCAG\Service\RedisService::class => function () {
        return new \MCAG\Service\RedisService();
    },

    \MCAG\Service\CacheService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\CacheService(
            $c->get(\MCAG\Service\RedisService::class)
        );
    },

    \MCAG\Service\QueueService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\QueueService(
            $c->get(PDO::class)
        );
    },

    \MCAG\Service\BackupVerificationService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\BackupVerificationService(
            $c->get(PDO::class),
            __DIR__ . '/../../storage/backups'
        );
    },

    \MCAG\Service\HealthCheckService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\HealthCheckService(
            $c->get(PDO::class),
            $c->get(\MCAG\Service\RedisService::class),
            $c->get(\MCAG\Service\QueueService::class),
            __DIR__ . '/../../storage'
        );
    },

    \MCAG\Service\JsonLogFormatter::class => function () {
        return new \MCAG\Service\JsonLogFormatter('MCAG');
    },

    // ===== EXISTING SERVICES =====
    \MCAG\Service\ValidationService::class => function () {
        return new \MCAG\Service\ValidationService();
    },

    \MCAG\Service\BackupService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\BackupService(
            __DIR__ . '/../../database.sqlite',
            __DIR__ . '/../../storage/backups',
            $c->get(LoggerInterface::class),
            14
        );
    },

    \MCAG\Debug\ResilienceMonitor::class => function (ContainerInterface $c) {
        return new \MCAG\Debug\ResilienceMonitor(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class),
            __DIR__ . '/../../storage'
        );
    },

    \MCAG\Service\EmailServiceInterface::class => function (ContainerInterface $c) {
        $config = [
            'host' => $_ENV['SMTP_HOST'] ?? 'smtp.example.com',
            'username' => $_ENV['SMTP_USER'] ?? 'user@example.com',
            'password' => $_ENV['SMTP_PASS'] ?? 'secret',
            'port' => $_ENV['SMTP_PORT'] ?? 587
        ];
        return new \MCAG\Service\SmtpEmailService($c->get(LoggerInterface::class), $config);
    },

    \MCAG\Service\PdfGenerationService::class => function () {
        return new \MCAG\Service\PdfGenerationService();
    },

    \MCAG\Service\RegistrationService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\RegistrationService(
            $c->get(\MCAG\GestioneSoci\SocioRepository::class),
            $c->get(\MCAG\Service\ValidationService::class),
            $c->get(\MCAG\Service\PdfGenerationService::class),
            $c->get(\MCAG\Service\EmailServiceInterface::class),
            $c->get(LoggerInterface::class)
        );
    },

    \MCAG\Service\Demo\DemoInvitationService::class => function (ContainerInterface $c) {
        return new \MCAG\Service\Demo\DemoInvitationService(
            $c->get(\MCAG\Service\EmailServiceInterface::class),
            $c->get(LoggerInterface::class),
            $_ENV['APP_URL'] ?? 'http://localhost/MCAG_Militare-Civile-Archivio-Gestionale/public',
            $c->get(PDO::class)
        );
    },

    // ===== EVENT BUS ARCHITECTURE (v5.0) =====
    \MCAG\Event\EventBusInterface::class => function (ContainerInterface $c) {
        $bus = new \MCAG\Event\EventBus($c->get(LoggerInterface::class));

        // Register Listeners
        // 1. Log Socio Creation
        $bus->subscribe(
            \MCAG\Event\Events\SocioCreatedEvent::class,
            new \MCAG\Event\Listeners\LogSocioCreationListener($c->get(LoggerInterface::class))
        );

        // 2. AI Indexing (RAG)
        $bus->subscribe(
            \MCAG\Event\Events\SocioCreatedEvent::class,
            $c->get(\MCAG\Event\Listeners\IndexSocioListener::class)
        );

        return $bus;
    },

    // ===== AI SERVICES (v5.0 Phase 3) =====
    \MCAG\AI\Providers\OllamaProvider::class => function (ContainerInterface $c) {
        return new \MCAG\AI\Providers\OllamaProvider(
            $c->get(LoggerInterface::class)
        );
    },

    \MCAG\AI\RAG\SimpleVectorStore::class => function (ContainerInterface $c) {
        return new \MCAG\AI\RAG\SimpleVectorStore(
            $c->get(LoggerInterface::class),
            __DIR__ . '/../../storage/ai/index.json'
        );
    },

    \MCAG\Event\Listeners\IndexSocioListener::class => function (ContainerInterface $c) {
        return new \MCAG\Event\Listeners\IndexSocioListener(
            $c->get(LoggerInterface::class),
            $c->get(\MCAG\AI\Providers\OllamaProvider::class),
            $c->get(\MCAG\AI\RAG\SimpleVectorStore::class)
        );
    },
];


