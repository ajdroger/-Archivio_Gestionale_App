<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Jobs;

use FratellanzaMilitare\Service\BackupService;

/**
 * Background Job per Backup Database
 */
class BackupDatabaseJob extends AbstractJob
{
    protected string $queue = 'maintenance';

    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function handle(): void
    {
        $this->backupService->executeBackup();
    }

    protected function getJobData(): array
    {
        return [
            'type' => 'database_backup',
            'timestamp' => time(),
        ];
    }
}
