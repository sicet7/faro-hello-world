<?php

namespace App;

use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Core\AbstractModule;

class Module extends AbstractModule implements HasConfigInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'app';
    }

    /**
     * @return array
     */
    public static function getConfigPaths(): array
    {
        return [
            dirname(__DIR__) . '/config',
        ];
    }
}
