<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAuditLogTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('audit_logs');
        $table->addColumn('timestamp', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('username', 'string')
            ->addColumn('action', 'string')
            ->addColumn('resource_id', 'string')
            ->addColumn('context_json', 'text', ['null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->create();
    }
}
