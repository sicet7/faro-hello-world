<?php

namespace Sicet7\Faro\Swoole;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Swoole\Http\ErrorManager;
use Sicet7\Faro\Swoole\Http\Server\ErrorHandler;
use Sicet7\Faro\Swoole\Http\Server\ResponseEmitter;
use Sicet7\Faro\Swoole\Http\Server\ResponseEmitterInterface;
use Sicet7\Faro\Swoole\Http\Server\WorkerState;
use Sicet7\Faro\Swoole\Http\ServerRequestBuilder;
use Sicet7\Faro\Swoole\Http\ServerRequestBuilderInterface;

use function DI\create;
use function DI\get;

class WebModule extends BaseModule
{
    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            ResponseEmitterInterface::class => create(ResponseEmitter::class)
                ->constructor(get(WorkerState::class)),
            ServerRequestBuilderInterface::class => create(ServerRequestBuilder::class)
                ->constructor(get(Psr17Factory::class), get(WorkerState::class)),
            Psr17Factory::class => create(Psr17Factory::class),
            ErrorHandler::class => function (LoggerInterface $logger, WorkerState $state, Config $config) {
                return ErrorHandler::create($logger, $state, $config);
            },
            ErrorManager::class => create(ErrorManager::class)
                ->constructor(get(ContainerInterface::class)),
        ];
    }
}
