<?php

declare(strict_types=1);

$phar = Phar::running(false);

if ($phar !== '') {
    return [
        'root' => dirname($phar),
    ];
}

return [
    'root' => dirname(__DIR__),
];
