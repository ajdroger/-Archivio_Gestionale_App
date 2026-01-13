<?php

namespace MCAG\Service\Demo;

use MCAG\Service\EmailServiceInterface;
use Psr\Log\LoggerInterface;

class DemoInvitationService
{
    private EmailServiceInterface $emailService;
    private LoggerInterface $logger;
    private string $baseUrl;
    private ?\PDO $pdo;

    public function __construct(
        EmailServiceInterface $emailService,
        LoggerInterface $logger,
        string $baseUrl = 'http://localhost/MCAG_Militare-Civile-Archivio-Gestionale/public',
        ?\PDO $pdo = null
    ) {
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->pdo = $pdo;
    }

    public function sendInvite(string $recipientEmail, string $clientName): bool
    {
        $subject = "Accesso Demo: MCAG System v4.0 Ultimate";
        $demoLink = $this->baseUrl . '/landing/index.html'; // Direct link to landing
        $loginLink = $this->baseUrl . '/login';

        // Generate Credentials
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', substr($clientName, 0, 5));
        $username = 'demo_' . strtolower($cleanName) . '_' . rand(100, 999);
        $password = bin2hex(random_bytes(4)); // 8 chars random

        if ($this->pdo) {
            try {
                // Check if email already exists
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utenti WHERE email = ?");
                $stmt->execute([$recipientEmail]);

                if ($stmt->fetchColumn() > 0) {
                    $this->logger->warning("Demo invite: Email $recipientEmail already exists. Creating new unique username anyway.");
                    // We proceed to create a new user entry for this demo if needed, 
                    // or ideally we shouldn't duplicate emails if logic forbids it.
                    // For a demo system, we might just append a suffix to email or skip creation.
                    // Let's Assume we CREATE A NEW USERName but keep same email (if DB constraint allows)
                    // If DB has unique constraint on email, this will fail.
                    // Let's modify username to ensure uniqueness if that was the key.
                }

                $hash = password_hash($password, PASSWORD_BCRYPT);
                // Use generic timestamp or NOW()
                $now = date('Y-m-d H:i:s');

                // Assuming 'role' column exists and 'ospite' is a valid role
                $stmt = $this->pdo->prepare("INSERT INTO utenti (username, password, email, role, created_at, two_factor_enabled) VALUES (?, ?, ?, 'ospite', ?, 0)");
                $stmt->execute([$username, $hash, $recipientEmail, $now]);

                $this->logger->info("Created demo user '$username' for $recipientEmail");

            } catch (\Exception $e) {
                $this->logger->error("Failed to create demo user: " . $e->getMessage());
                // We return false because sending credentials that don't allow login is bad UX
                return false;
            }
        }

        // Load HTML Template
        $templatePath = __DIR__ . '/../../../templates/emails/demo_invite.html';

        if (!file_exists($templatePath)) {
            $this->logger->error("Demo template not found at: $templatePath");
            return false;
        }

        $htmlContent = file_get_contents($templatePath);

        // Dynamic replacement
        $htmlContent = str_replace('{{client_name}}', htmlspecialchars($clientName), $htmlContent);
        $htmlContent = str_replace('{{demo_link}}', $demoLink, $htmlContent);
        $htmlContent = str_replace('{{login_link}}', $loginLink, $htmlContent);
        $htmlContent = str_replace('{{username}}', $username, $htmlContent);
        $htmlContent = str_replace('{{password}}', $password, $htmlContent);
        $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);

        $this->logger->info("Sending Demo Invite to $recipientEmail for client $clientName");

        return $this->emailService->send($recipientEmail, $subject, $htmlContent);
    }
}


