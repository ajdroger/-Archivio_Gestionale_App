<?php

/**
 * CLI Tool - Invia Invito Demo
 * Usage: php bin/demo/invite_client.php <email> "<client_name>"
 */

require __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use MCAG\Service\Demo\DemoInvitationService;

// Setup Container
$containerBuilder = new ContainerBuilder();

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Load definitions manually since we are in a standalone script context
// Adjust paths as necessary based on how your app loads config
// Adjust paths as necessary based on how your app loads config
if (file_exists(__DIR__ . '/../../config/container.php')) {
    $definitionFiles = require __DIR__ . '/../../config/container.php';
    foreach ($definitionFiles as $file) {
        $containerBuilder->addDefinitions($file);
    }
    $container = $containerBuilder->build();
} else {
    // Fallback minimal loading if container.php structure is complex
    // This assumes standard setup. If container.php returns the built container, perfect.
    // If it returns definitions, we use addDefinitions.
    // Let's assume standard behavior for now, or use direct construction if simple.
    $definitions = require __DIR__ . '/../../config/services.php'; // Assuming services are here
    $containerBuilder->addDefinitions($definitions);
    $container = $containerBuilder->build();
}

$email = $argv[1] ?? null;
$name = $argv[2] ?? 'Cliente';

if (!$email) {
    echo "\n[ERROR] Specificare un'email.\n";
    echo "Usage: php bin/demo/invite_client.php user@example.com \"Nome Cliente\"\n\n";
    exit(1);
}

try {
    // We need to ensure DemoInvitationService is available in container
    // If not autowired, we might need to manually construct or add to definitions.
    // For this CLI, let's try to get it.

    // Quick Fix: Manually checking if Service exists in container, if not, we build it.
    // Assuming standard autowiring might not catch the new class immediately without config update.

    if (!$container->has(DemoInvitationService::class)) {
        // Build dependencies manually for this script if not in container
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
        $emailService = $container->get(\MCAG\Service\EmailServiceInterface::class);
        $demoService = new DemoInvitationService($emailService, $logger);
    } else {
        $demoService = $container->get(DemoInvitationService::class);
    }

    echo "Invio invito a $name <$email>...\n";

    if ($demoService->sendInvite($email, $name)) {
        echo "[SUCCESS] Email inviata correttamente!\n";
    } else {
        echo "[ERROR] Impossibile inviare email. Controlla i log.\n";
    }

} catch (\Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

