<?php

namespace Sicet7\Faro\Config\Console;

use Sicet7\Faro\Config\Console\Commands\ShowCommand;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;

class Module extends AbstractModule implements HasCommandsInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-config-console';
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [
            'faro-config',
            'faro-console',
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
