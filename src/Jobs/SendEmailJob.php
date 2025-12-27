<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Jobs;

use FratellanzaMilitare\Service\EmailServiceInterface;

/**
 * Background Job per Invio Email
 */
class SendEmailJob extends AbstractJob
{
    protected string $queue = 'emails';

    private EmailServiceInterface $emailService;
    private string $to;
    private string $subject;
    private string $body;

    public function __construct(
        EmailServiceInterface $emailService,
        string $to,
        string $subject,
        string $body
    ) {
        $this->emailService = $emailService;
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function handle(): void
    {
        $this->emailService->send($this->to, $this->subject, $this->body);
    }

    protected function getJobData(): array
    {
        return [
            'type' => 'send_email',
            'to' => $this->to,
            'subject' => $this->subject,
        ];
    }
}
