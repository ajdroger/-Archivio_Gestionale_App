<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        // Table: soci
        if (!$this->hasTable('soci')) {
            $tableSoci = $this->table('soci', ['id' => false, 'primary_key' => ['codice_fiscale']]);
            $tableSoci->addColumn('codice_fiscale', 'string', ['limit' => 16])
                ->addColumn('matricola', 'string', ['null' => true])
                ->addColumn('nome', 'string')
                ->addColumn('cognome', 'string')
                ->addColumn('data_nascita', 'string', ['null' => true])
                ->addColumn('indirizzo', 'string', ['null' => true])
                ->addColumn('email', 'string', ['null' => true])
                ->addColumn('telefono', 'string', ['null' => true])
                ->addColumn('stato', 'string', ['null' => true])
                ->create();
        }

        // Table: documenti
        if (!$this->hasTable('documenti')) {
            $tableDocs = $this->table('documenti', ['id' => false, 'primary_key' => ['id_univoco']]);
            $tableDocs->addColumn('id_univoco', 'string')
                ->addColumn('nome_file', 'string')
                ->addColumn('hash_sha256', 'string', ['null' => true])
                ->addColumn('stato', 'string', ['null' => true])
                ->addColumn('data_caricamento', 'string', ['null' => true])
                ->addColumn('tipo_documento', 'string', ['null' => true])
                ->addColumn('codice_fiscale_socio', 'string')
                ->addColumn('anno_solare', 'integer', ['null' => true])
                ->addColumn('quota_versata', 'float', ['null' => true])
                ->addColumn('metodo_pagamento', 'string', ['null' => true])
                ->addColumn('trattamento_dati', 'integer', ['null' => true])
                ->addColumn('cessione_terzi', 'integer', ['null' => true])
                ->addColumn('marketing', 'integer', ['null' => true])
                ->addColumn('data_firma', 'string', ['null' => true])
                ->addForeignKey('codice_fiscale_socio', 'soci', 'codice_fiscale', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_documenti_soci'
                ])
                ->create();
        }

        // Table: users
        if (!$this->hasTable('users')) {
            $tableUsers = $this->table('users'); // id is auto-created by default in Phinx usually, or specify
            $tableUsers->addColumn('username', 'string', ['limit' => 255])
                ->addColumn('password_hash', 'string')
                ->addColumn('role', 'string', ['limit' => 50])
                ->addColumn('created_at', 'datetime') // Phinx uses datetime type
                ->addIndex(['username'], ['unique' => true])
                ->create();

            // Seed admin
            $hash = password_hash('password', PASSWORD_DEFAULT);
            $this->execute("INSERT INTO users (username, password_hash, role, created_at) VALUES ('admin', '$hash', 'Amministratore', datetime('now'))");
        }
    }
}
