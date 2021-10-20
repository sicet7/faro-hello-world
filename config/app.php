<?php

declare(strict_types=1);

use function Config\env;

return [
    'name' => 'test123',
    'env' => env('APP_ENV', 'dev'),
];
