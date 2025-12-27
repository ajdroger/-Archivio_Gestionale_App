<?php

use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'development',
            'development' => [
                'adapter' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'name' => $_ENV['DB_DATABASE'] ?? 'fratellanza_db',
                'user' => $_ENV['DB_USERNAME'] ?? 'root',
                'pass' => $_ENV['DB_PASSWORD'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'charset' => 'utf8mb4',
            ],
            'production' => [
                'adapter' => 'mysql',
                'host' => $_ENV['DB_HOST'],
                'name' => $_ENV['DB_DATABASE'],
                'user' => $_ENV['DB_USERNAME'],
                'pass' => $_ENV['DB_PASSWORD'],
                'port' => $_ENV['DB_PORT'],
                'charset' => 'utf8mb4',
            ],
            'testing' => [
                'adapter' => 'mysql',
                'host' => '127.0.0.1',
                'name' => 'fratellanza_test',
                'user' => 'root',
                'pass' => '',
                'port' => 3306,
                'charset' => 'utf8mb4',
            ]
        ],
        'version_order' => 'creation'
    ];
