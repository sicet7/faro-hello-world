<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Sicet7\Faro\Config\ConfigMap;
use Nyholm\Psr7\Factory\Psr17Factory;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Swoole\Http\Server\Event\WorkerStart;
use Sicet7\Faro\Swoole\Http\Server\Event\WorkerStop;
use Sicet7\Faro\Web\ModuleContainer;
use Sicet7\Faro\Web\RequestEvent;
use Sicet7\Faro\Web\WebContainer;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

use function DI\create;

class Runner
{
    private ?WebContainer $container = null;

    private ?array $config = null;

    /**
     * @param array|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    /**
     * @param Server $server
     */
    public function onStart(Server $server): void
    {
        echo 'Server started listening on: ' . $server->host . ':' . $server->port . PHP_EOL;
    }

    /**
     * @param Server $server
     */
    public function onShutdown(Server $server): void
    {
        echo 'Server is shutting down.' . PHP_EOL;
    }

    /**
     * @param Server $server
     * @param int $workerId
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Sicet7\Faro\Config\Exceptions\ConfigException
     * @throws \Sicet7\Faro\Core\Exception\ModuleException
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        $customDefinitions = [
            Server::class => $server,
            'swoole.worker.id' => $workerId,
            Psr17Factory::class => create(Psr17Factory::class),
        ];
        if (!empty($this->config)) {
            $customDefinitions[ConfigMap::class] = new ConfigMap();
            $customDefinitions[ConfigMap::class]->buildMap($this->config);
        }
        $this->container = ModuleContainer::buildWebContainer($customDefinitions);
        $this->container->get(Dispatcher::class)->dispatch(new WorkerStart($server, $workerId));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function onRequest(Request $request, Response $response): void
    {
        $psr17Factory = $this->container->get(Psr17Factory::class);
        $requestConverter = new SwooleServerRequestConverter(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $eventDispatcher = $this->container->get(Dispatcher::class);
        $requestEvent = new RequestEvent($requestConverter->createFromSwoole($request));
        $eventDispatcher->dispatch($requestEvent);
        if ($requestEvent->getResponse() === null) {
            $response->setStatusCode(404, 'Response was not generated');
            $response->end('Response was not generated');
            return;
        }
        $responseConverter = new SwooleResponseConverter($response);
        $responseConverter->send($requestEvent->getResponse());
    }

    /**
     * @param Server $server
     * @param int $workerId
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        $this->container->get(Dispatcher::class)->dispatch(new WorkerStop($server, $workerId));
        $this->container = null;
    }
}
