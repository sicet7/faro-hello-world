<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

abstract class AbstractModule extends \Sicet7\Faro\Core\AbstractModule
{
    /**
     * override this method to add custom commands to the CommandLoader
     *
     * @return array
     */
    public static function getCommandDefinitions(): array
    {
        return [];
    }
}
