<?php

use function Config\concat;
use function Config\val;

return [
    'connection' => [
        //'url' => 'sqlite:///somedb.sqlite',
        'dbname' => 'testDatabase',
        'user' => 'testUser',
        'password' => 'testPassword',
        'host' => 'localhost',
        'driver' => 'pdo_pgsql',
    ],
    'orm' => [
        'proxyClasses' => [
            'dir' => concat(val('dir.include'), '/proxies'),
            'namespace' => 'Proxies\\',
        ],
    ],
    'migrations' => [
        'App\\Database\\Migrations' => concat(val('dir.include'), '/app/Database/Migrations'),
    ],
];
