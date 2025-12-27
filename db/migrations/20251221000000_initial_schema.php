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
            $tableSoci->addColumn('codice_fiscale', 'string', ['limit' => 16, 'null' => false])
                ->addColumn('matricola', 'string', ['null' => true])
                ->addColumn('nome', 'string', ['null' => true])
                ->addColumn('cognome', 'string', ['null' => true])
                ->addColumn('data_nascita', 'date', ['null' => true]) // MySQL DATE
                ->addColumn('indirizzo', 'string', ['null' => true])
                ->addColumn('email', 'string', ['null' => true])
                ->addColumn('telefono', 'string', ['null' => true])
                ->addColumn('stato_iscrizione', 'string', ['null' => true]) // Match Repo
                ->create();
        }

        // Table: documenti
        if (!$this->hasTable('documenti')) {
            $tableDocs = $this->table('documenti', ['id' => false, 'primary_key' => ['id_univoco']]);
            $tableDocs->addColumn('id_univoco', 'string', ['null' => false])
                ->addColumn('nome_file', 'string', ['null' => true])
                ->addColumn('hash_file', 'string', ['null' => true]) // Match Repo
                ->addColumn('stato', 'string', ['null' => true])
                ->addColumn('data_caricamento', 'datetime', ['null' => true]) // MySQL DATETIME
                ->addColumn('tipo_documento', 'string', ['null' => true])
                ->addColumn('socio_cf', 'string', ['null' => false]) // Match Repo
                ->addColumn('anno_solare', 'integer', ['null' => true])
                ->addColumn('quota_versata', 'float', ['null' => true])
                ->addColumn('metodo_pagamento', 'string', ['null' => true])
                ->addColumn('trattamento_dati', 'integer', ['null' => true])
                ->addColumn('cessione_terzi', 'integer', ['null' => true])
                ->addColumn('marketing', 'integer', ['null' => true])
                ->addColumn('data_firma', 'datetime', ['null' => true])
                ->addForeignKey('socio_cf', 'soci', 'codice_fiscale', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_documenti_soci'
                ])
                ->create();
        }

        // Table: users
        if (!$this->hasTable('users')) {
            $tableUsers = $this->table('users');
            $tableUsers->addColumn('username', 'string', ['limit' => 255])
                ->addColumn('password_hash', 'string')
                ->addColumn('role', 'string', ['limit' => 50])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('totp_secret', 'string', ['null' => true, 'limit' => 255])
                ->addIndex(['username'], ['unique' => true])
                ->create();

            // Seed admin
            $hash = password_hash('password', PASSWORD_DEFAULT);
            $this->execute("INSERT INTO users (username, password_hash, role, created_at) VALUES ('admin', '$hash', 'Amministratore', NOW())");
        }
    }
}
