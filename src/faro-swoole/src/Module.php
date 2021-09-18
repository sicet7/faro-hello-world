<?php

namespace Sicet7\Faro\Swoole;

use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Swoole\Commands\StartCommand;
use Sicet7\Faro\Swoole\Http\Server\Handler;

use function DI\create;

class Module extends AbstractModule implements HasCommandsInterface
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
            Handler::class => create(Handler::class),
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
}
