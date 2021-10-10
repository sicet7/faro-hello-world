<?php

namespace Sicet7\Faro\ORM\Console\Interfaces;

interface HasMigrationsInterface
{
    /**
     * @return string[]
     */
    public static function getMigrations(): array;
}
