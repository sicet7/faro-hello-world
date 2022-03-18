<?php

namespace Sicet7\Faro\Config\Console;

use Sicet7\Faro\Config\Console\Commands\ShowCommand;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\BaseModule;

class Module extends BaseModule implements HasCommandsInterface
{
    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Config\Module::class,
            \Sicet7\Faro\Console\Module::class,
        ];
    }

    /**
     * @return array
     */
    public static function getCommands(): array
    {
        return [
            ShowCommand::class,
        ];
    }
}
