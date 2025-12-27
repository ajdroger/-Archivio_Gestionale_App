<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration per tabella jobs - Queue System
 * 
 * Crea tabella per gestire background jobs con retry logic e scheduling.
 */
final class CreateJobsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('jobs');

        $table->addColumn('queue', 'string', ['limit' => 255, 'null' => false, 'default' => 'default'])
            ->addColumn('payload', 'text', ['null' => false])
            ->addColumn('attempts', 'integer', ['null' => false, 'default' => 0, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY])
            ->addColumn('reserved_at', 'integer', ['null' => true])
            ->addColumn('available_at', 'integer', ['null' => false])
            ->addColumn('created_at', 'integer', ['null' => false])
            ->addColumn('failed_at', 'integer', ['null' => true])
            ->addColumn('error_message', 'text', ['null' => true])
            ->addIndex(['queue', 'reserved_at'])
            ->addIndex(['queue', 'available_at'])
            ->create();

        // Failed jobs table
        $failedTable = $this->table('failed_jobs');

        $failedTable->addColumn('queue', 'string', ['limit' => 255])
            ->addColumn('payload', 'text')
            ->addColumn('exception', 'text')
            ->addColumn('failed_at', 'integer')
            ->create();
    }
}
