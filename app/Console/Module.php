<?php

namespace App\Console;

use App\Console\Commands\HelloWorldCommand;
use App\Console\Commands\PingCommand;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;

class Module extends AbstractModule implements HasCommandsInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'console-app';
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [
            'faro-console'
        ];
    }

    /**
     * @return array
     */
    public static function getCommands(): array
    {
        return [
            HelloWorldCommand::class,
            PingCommand::class,
        ];
    }
}
