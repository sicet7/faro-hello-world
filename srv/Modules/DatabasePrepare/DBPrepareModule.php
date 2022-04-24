<?php

namespace Server\Modules\DatabasePrepare;

use Server\Modules\Core\CoreModule;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Module as ConfigModule;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Sicet7\Faro\ORM\Console\Module as ORMConsoleModule;
use function DI\create;
use function DI\get;

class DBPrepareModule extends BaseModule implements HasListenersInterface
{
    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            ConfigModule::class,
            ORMConsoleModule::class,
            CoreModule::class,
        ];
    }

    /**
     * @return array
     */
    public static function getListeners(): array
    {
        return PSR4::getFQCNs(__NAMESPACE__ . '\\Listeners', __DIR__ . '/Listeners');
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            HasMigrationsCheck::class => create(HasMigrationsCheck::class)
                ->constructor(get(Config::class)),
        ];
    }
}
