<?php

namespace Sicet7\Faro\Swoole;

use Sicet7\Faro\Console\AbstractModule;
use Sicet7\Faro\Swoole\Commands\StartCommand;
use Sicet7\Faro\Swoole\Commands\StopCommand;

class Module extends AbstractModule
{
    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'swoole';
    }

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getCommandDefinitions(): array
    {
        return [
            'swoole:server:start' => StartCommand::class,
            'swoole:server:stop' => StopCommand::class,
        ];
    }
}
