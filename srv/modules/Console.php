<?php

namespace Server\Modules;

use Server\App\Console\Services\MigrationsService;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Module as ConfigModule;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Sicet7\Faro\ORM\Console\Module as ORMConsoleModule;

use function DI\create;
use function DI\get;

class Console extends BaseModule implements HasListenersInterface
{
    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            ConfigModule::class,
            ORMConsoleModule::class,
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
            MigrationsService::class => create(MigrationsService::class)
                ->constructor(get(Config::class)),
        ];
    }
}
