<?php

namespace Sicet7\Faro\Log;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Core\AbstractModule;

use function DI\create;
use function DI\get;

class Module extends AbstractModule
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-log';
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            Logger::class => create(Logger::class)
                ->constructor(
                    'default',
                    [],
                    [],
                    new \DateTimeZone('UTC')
                ),
            LoggerInterface::class => get(Logger::class),
        ];
    }
}
