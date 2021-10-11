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
    'proxyClasses' => [
        'dir' => concat(val('dir.root'), '/proxies'),
        'namespace' => 'Proxies\\',
    ],
];
