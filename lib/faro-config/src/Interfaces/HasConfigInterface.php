<?php

namespace Sicet7\Faro\Config\Interfaces;

interface HasConfigInterface
{
    /**
     * @return string[]
     */
    public static function getConfigPaths(): array;
}
