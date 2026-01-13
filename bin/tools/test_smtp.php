<?php

use MCAG\Service\SmtpEmailService;

require __DIR__ . '/../../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "📧 SMTP Test Utility\n";
echo "====================\n";

$targetEmail = $_ENV['SMTP_USER'] ?? null;

if (!$targetEmail) {
    die("❌ Error: SMTP_USER not set in .env\n");
}

echo "Context: Sending verify email to self ($targetEmail)...\n";

try {
    // Configuration Array from ENV
    $config = [
        'host' => $_ENV['SMTP_HOST'],
        'username' => $_ENV['SMTP_USER'],
        'password' => $_ENV['SMTP_PASS'],
        'port' => $_ENV['SMTP_PORT'],
        'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls'
    ];

    // Simple Console Logger
    $logger = new class implements \Psr\Log\LoggerInterface {
        public function emergency($message, array $context = []): void
        {
            echo "[EMERGENCY] $message\n";
        }
        public function alert($message, array $context = []): void
        {
            echo "[ALERT] $message\n";
        }
        public function critical($message, array $context = []): void
        {
            echo "[CRITICAL] $message\n";
        }
        public function error($message, array $context = []): void
        {
            echo "[ERROR] $message\n";
        }
        public function warning($message, array $context = []): void
        {
            echo "[WARNING] $message\n";
        }
        public function notice($message, array $context = []): void
        {
            echo "[NOTICE] $message\n";
        }
        public function info($message, array $context = []): void
        {
            echo "[INFO] $message\n";
        }
        public function debug($message, array $context = []): void
        {
            echo "[DEBUG] $message\n";
        }
        public function log($level, $message, array $context = []): void
        {
            echo "[$level] $message\n";
        }
    };

    echo "Config: Host={$config['host']}, Port={$config['port']}, User={$config['username']}\n";

    $service = new SmtpEmailService($logger, $config);

    $subject = "Test SMTP Configuration - " . date('Y-m-d H:i:s');
    $body = "<h1>SMTP Test Success</h1><p>This email confirms that your SMTP configuration in <code>.env</code> is correct.</p><p>Sent from: Fratellanza Militare CLI Tool</p>";

    if ($service->send($targetEmail, $subject, $body)) {
        echo "✅ SUCCESS: Email sent to $targetEmail\n";
    } else {
        echo "❌ FAILURE: Check logs above for details.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

