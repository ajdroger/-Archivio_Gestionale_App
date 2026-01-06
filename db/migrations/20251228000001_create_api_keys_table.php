<?php

use Phinx\Migration\AbstractMigration;

class CreateApiKeysTable extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('api_keys')) {
            $table = $this->table('api_keys');

            $table->addColumn('user_id', 'integer', ['null' => false])
                ->addColumn('key_hash', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('key_prefix', 'string', ['limit' => 8, 'null' => false, 'comment' => 'First 8 chars for identification'])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Human-readable name'])
                ->addColumn('scopes', 'text', ['null' => false, 'comment' => 'Comma-separated: soci:read,soci:write,documenti:read'])
                ->addColumn('rate_limit', 'integer', ['default' => 1000, 'comment' => 'Requests per hour'])
                ->addColumn('active', 'boolean', ['default' => true])
                ->addColumn('expires_at', 'datetime', ['null' => true])
                ->addColumn('last_used_at', 'datetime', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['user_id'])
                ->addIndex(['key_hash'], ['unique' => true])
                ->addIndex(['key_prefix'])
                ->addIndex(['expires_at'])
                ->addIndex(['active'])
                ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        // Table for API request tracking (rate limiting)
        if (!$this->hasTable('api_request_tracking')) {
            $tracking = $this->table('api_request_tracking');
            $tracking->addColumn('api_key_id', 'integer', ['null' => false])
                ->addColumn('endpoint', 'string', ['limit' => 255])
                ->addColumn('method', 'string', ['limit' => 10])
                ->addColumn('ip_address', 'string', ['limit' => 45])
                ->addColumn('status_code', 'integer')
                ->addColumn('response_time_ms', 'integer')
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['api_key_id', 'created_at'])
                ->addIndex(['created_at']) // For cleanup job
                ->addForeignKey('api_key_id', 'api_keys', 'id', ['delete' => 'CASCADE'])
                ->create();
        }
    }
}
