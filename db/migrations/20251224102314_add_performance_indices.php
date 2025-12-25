<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPerformanceIndices extends AbstractMigration
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
        // Audit Logs (Used for filtering and sorting)
        $this->table('audit_logs')
            ->addIndex(['timestamp'])
            ->addIndex(['user_id'])
            ->addIndex(['action'])
            ->addIndex(['resource_id'])
            ->update();

        // Soci (Used for filtering by status and searching)
        $this->table('soci')
            ->addIndex(['stato'])
            // Composite index for fast searching if needed, but single indices are often enough for SQLite
            ->addIndex(['codice_fiscale'])
            ->addIndex(['nome', 'cognome'])
            ->update();
    }
}
