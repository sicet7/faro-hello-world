<?php

use function Config\concat;
use function Config\env;
use function Config\val;

return [
    'connection' => [
        //'url' => 'sqlite:///somedb.sqlite',
        'dbname' => env('DB_NAME', 'testDatabase'),
        'user' => env('DB_USER', 'testUser'),
        'password' => env('DB_PASSWORD', 'testPassword'),
        'host' => env('DB_HOST', 'localhost'),
        'driver' => env('DB_DRIVER', 'pdo_pgsql'),
    ],
    'orm' => [
        'proxyClasses' => [
            'dir' => concat(val('dir.root'), '/srv/database/Proxies'),
            'namespace' => 'Proxies\\',
        ],
    ],
    'migrations' => [
        'Migrations' => concat(val('dir.root'), '/srv/database/Migrations'),
    ],
];
