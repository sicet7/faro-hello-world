<?php

namespace App\Console;

use App\Console\Listeners\ServerListener;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;

class Module extends BaseModule implements HasCommandsInterface, HasListenersInterface
{
    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Console\Module::class,
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
     * @return array
     */
    public static function getListeners(): array
    {
        return [
            ServerListener::class,
        ];
    }
}
