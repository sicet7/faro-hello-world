<?php

namespace Sicet7\Faro\Swoole;

use Sicet7\Faro\Console\HasCommandDefinitions;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Swoole\Commands\StartCommand;
use Sicet7\Faro\Swoole\Http\Server\Handler;

use function DI\create;

class Module extends AbstractModule implements HasCommandDefinitions
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'swoole';
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
            'config',
        ];
    }

    /**
     * @return string[]
     */
    public static function getCommandDefinitions(): array
    {
        return [
            'swoole:server:start' => StartCommand::class,
        ];
    }
}
