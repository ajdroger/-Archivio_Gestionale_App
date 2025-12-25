<?php

namespace FratellanzaMilitare\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;

class SmtpEmailService implements EmailServiceInterface
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['port'];

            // Recipients
            $mail->setFrom($this->config['username'], 'Fratellanza Militare');
            $mail->addAddress($to);

            // Attachments
            foreach ($attachments as $path) {
                if (file_exists($path)) {
                    $mail->addAttachment($path);
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            $this->logger->info("Email enviata a: $to", ['subject' => $subject]);
            return true;
        } catch (Exception $e) {
            $this->logger->error("Impossibile inviare email. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
