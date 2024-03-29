<?php

namespace Sicet7\Faro\Log;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Core\BaseModule;

use function DI\create;
use function DI\get;

class Module extends BaseModule
{
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
                    get(\DateTimeZone::class)
                ),
            LoggerInterface::class => get(Logger::class),
        ];
    }
}
