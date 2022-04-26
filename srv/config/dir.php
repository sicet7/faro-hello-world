<?php

declare(strict_types=1);

use function Config\concat;
use function Config\val;

return [
    'root' => dirname(__DIR__, 2),
    'var' => concat(val('dir.root'), '/var'),
    'log' => concat(val('dir.var'), '/log'),
];
