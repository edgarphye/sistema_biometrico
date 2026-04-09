<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/migrations/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => 'development', // 'default_database' es el nombre correcto de la clave
        
        'development' => [
            'adapter' => getenv('DB_ADAPTER') ?: 'mysql',
            'host'    => getenv('DB_HOST') ?: 'localhost',
            'name'    => getenv('DB_NAME') ?: 'sistema_biometrico',
            'user'    => getenv('DB_USER') ?: 'root',
            'pass'    => getenv('DB_PASS') ?: '', // Dejar vacío si no hay contraseña
            'port'    => getenv('DB_PORT') ?: 3306,
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
