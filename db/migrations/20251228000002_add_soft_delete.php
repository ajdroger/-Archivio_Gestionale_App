<?php
use Phinx\Migration\AbstractMigration;

class AddSoftDelete extends AbstractMigration
{
    public function change(): void
    {
        $tables = ['soci', 'documenti', 'users'];

        foreach ($tables as $table) {
            $t = $this->table($table);
            if (!$t->hasColumn('deleted_at')) {
                $t->addColumn('deleted_at', 'datetime', ['null' => true])
                    ->addIndex(['deleted_at'])
                    ->update();
            }
        }
    }
}
