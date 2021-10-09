<?php

use function Config\concat;
use function Config\val;

return [
    'connection' => [
        'url' => 'sqlite:///somedb.sqlite',
    ],
    'proxyClasses' => [
        'dir' => concat(val('dir.root'), '/proxies'),
        'namespace' => 'Proxies\\',
    ],
];
