<?php

namespace Server\App\Console;

use Server\App\Console\Services\MigrationsService;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Module as ConfigModule;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Sicet7\Faro\Event\Module as EventModule;
use Sicet7\Faro\ORM\Console\Module as ORMConsoleModule;

use function DI\create;
use function DI\get;

class Module extends BaseModule implements HasListenersInterface
{
    /**
     * @var bool
     */
    protected static bool $enableAttributeDefinitions = true;

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            ConfigModule::class,
            ORMConsoleModule::class,
            EventModule::class,
        ];
    }

    /**
     * @return array
     */
    public static function getListeners(): array
    {
        return PSR4::getFQCNs('Server\\App\\Console\\Listeners', dirname(__DIR__) . '/app/Console/Listeners');
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
//            MigrationsService::class => create(MigrationsService::class)
//                ->constructor(get(Config::class)),
        ];
    }
}