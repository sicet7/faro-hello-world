<?php

declare(strict_types=1);

$phar = Phar::running(false);

if ($phar !== '') {
    return [
        'root' => dirname($phar),
        'include' => dirname(__DIR__),
    ];
}

return [
    'root' => dirname(__DIR__),
    'include' => dirname(__DIR__),
];
