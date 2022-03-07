<?php

namespace App\Console;

use App\Console\Commands\HelloWorldCommand;
use App\Console\Commands\NewEntry;
use App\Console\Commands\PingCommand;
use App\Console\Database\Migrations\CreateTestTable;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\ORM\Console\Interfaces\HasMigrationsInterface;

class Module extends AbstractModule implements HasCommandsInterface, HasMigrationsInterface
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
        return PSR4::getFQCNs('App\\Console\\Commands', __DIR__ . '/Commands');
    }

    /**
     * @return string[]
     */
    public static function getMigrations(): array
    {
        return [
            CreateTestTable::class,
        ];
    }
}
