<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RebrandToMcag extends AbstractMigration
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
        // Check if settings table exists
        if ($this->hasTable('settings')) {
            // Rebrand 'organization_name' in settings if exists
            // ESCAPE 'key' because it is a reserved word in MySQL
            $this->execute("UPDATE settings SET value = REPLACE(value, 'Fratellanza Militare Firenze', 'MCAG Militare Civile Archivio Gestionale') WHERE `key` LIKE '%organization%' OR `key` LIKE '%name%'");
            $this->execute("UPDATE settings SET value = REPLACE(value, 'Fratellanza Militare', 'MCAG') WHERE `key` LIKE '%organization%' OR `key` LIKE '%name%'");

            // Optional: Update other generic content in settings
            $this->execute("UPDATE settings SET value = REPLACE(value, 'Fratellanza Militare', 'MCAG') WHERE value LIKE '%Fratellanza Militare%'");
        }
    }
}
