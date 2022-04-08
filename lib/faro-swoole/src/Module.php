<?php

namespace Sicet7\Faro\Swoole;

use DI\FactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Swoole\Http\Server\Initializer;
use Sicet7\Faro\Swoole\Http\Server\Runner;
use Sicet7\Faro\Swoole\Http\Server\RunnerInterface;

use function DI\create;
use function DI\get;

class Module extends BaseModule implements HasCommandsInterface, HasConfigInterface
{
    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            Initializer::class => create(Initializer::class)
                ->constructor(get(FactoryInterface::class)),
            RunnerInterface::class => create(Runner::class)
                ->constructor(get(EventDispatcherInterface::class)),
        ];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Console\Module::class,
            \Sicet7\Faro\Config\Module::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getCommands(): array
    {
        return PSR4::getFQCNs(
            'Sicet7\\Faro\\Swoole\\Commands',
            __DIR__ . '/Commands'
        );
    }

    /**
     * @return array
     */
    public static function getConfigPaths(): array
    {
        return [
            dirname(__DIR__) . '/config/swoole.php',
        ];
    }
}
