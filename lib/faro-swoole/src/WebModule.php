<?php

namespace Sicet7\Faro\Swoole;

use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Swoole\Http\MaintenanceManager;
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
            SwooleServerRequestConverter::class => create(SwooleServerRequestConverter::class)
                ->constructor(
                    get(ServerRequestFactoryInterface::class),
                    get(UriFactoryInterface::class),
                    get(UploadedFileFactoryInterface::class),
                    get(StreamFactoryInterface::class)
                ),
            ResponseEmitterInterface::class => create(ResponseEmitter::class)
                ->constructor(get(WorkerState::class)),
            ServerRequestBuilderInterface::class => create(ServerRequestBuilder::class)
                ->constructor(
                    get(WorkerState::class),
                    get(SwooleServerRequestConverter::class)
                ),
            ErrorHandler::class => function (LoggerInterface $logger, WorkerState $state, Config $config) {
                return ErrorHandler::create($logger, $state, $config);
            },
            MaintenanceManager::class => create(MaintenanceManager::class)
                ->constructor(get(Config::class), get(WorkerState::class)),
        ];
    }
}
