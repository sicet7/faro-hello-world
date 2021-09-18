<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sicet7\Faro\Config\ConfigMap;
use Nyholm\Psr7\Factory\Psr17Factory;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Core\Exception\ContainerException;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Swoole\Http\Server\Event\WorkerStart;
use Sicet7\Faro\Swoole\Http\Server\Event\WorkerStop;
use Sicet7\Faro\Web\ModuleContainer;
use Sicet7\Faro\Web\RequestEvent;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

use function DI\create;

class Runner
{
    /**
     * @var ContainerInterface|null
     */
    private ?ContainerInterface $container = null;

    /**
     * @var array|null
     */
    private ?array $config = null;

    /**
     * @param array|null $config
     * @return void
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    /**
     * @param Server $server
     * @return void
     */
    public function onStart(Server $server): void
    {
        echo 'Server started listening on: ' . $server->host . ':' . $server->port . PHP_EOL;
    }

    /**
     * @param Server $server
     * @return void
     */
    public function onShutdown(Server $server): void
    {
        echo 'Server is shutting down.' . PHP_EOL;
    }

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     * @throws NotFoundException|ConfigException|ModuleException|ContainerException|DependencyException
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
        $this->container = ModuleContainer::buildContainer($customDefinitions);
        $this->container->get(EventDispatcherInterface::class)->dispatch(new WorkerStart($server, $workerId));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @throws DependencyException
     * @throws NotFoundException
     * @return void
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
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
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
     * @throws DependencyException|NotFoundException
     * @return void
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        $this->container->get(EventDispatcherInterface::class)->dispatch(new WorkerStop($server, $workerId));
        $this->container = null;
    }
}
