<?php

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
                'adapter' => 'sqlite',
                'name' => '%%PHINX_CONFIG_DIR%%/database', // Maps to database.sqlite (suffix added by adapter usually, but check)
                'suffix' => '.sqlite', // Phinx sqlite adapter appends suffix if not present
            ],
            'production' => [
                'adapter' => 'sqlite',
                'name' => '%%PHINX_CONFIG_DIR%%/database',
                'suffix' => '.sqlite',
            ],
            'testing' => [
                'adapter' => 'sqlite',
                'memory' => true
            ]
        ],
        'version_order' => 'creation'
    ];
