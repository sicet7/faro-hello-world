<?php

namespace Sicet7\Faro\Swoole;

use DI\FactoryInterface;
use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Swoole\Commands\StartCommand;
use Sicet7\Faro\Swoole\Http\Server\Initializer;
use Sicet7\Faro\Swoole\Http\Server\Runner;
use Sicet7\Faro\Swoole\Http\Server\RunnerInterface;

use function DI\create;
use function DI\get;

class Module extends AbstractModule implements HasCommandsInterface, HasConfigInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-swoole';
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            Initializer::class => create(Initializer::class)
                ->constructor(get(FactoryInterface::class)),
            RunnerInterface::class => create(Runner::class),
        ];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'faro-console',
            'faro-config',
        ];
    }

    /**
     * @return string[]
     */
    public static function getCommands(): array
    {
        return [
            StartCommand::class,
        ];
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
