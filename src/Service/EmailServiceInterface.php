<?php

namespace FratellanzaMilitare\Service;

interface EmailServiceInterface
{
    public function send(string $to, string $subject, string $body, array $attachments = []): bool;
}
