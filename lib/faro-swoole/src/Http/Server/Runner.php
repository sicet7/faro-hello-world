<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use DI\DependencyException;
use DI\NotFoundException;
use Monolog\Utils;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Swoole\Http\ErrorManager;
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
use function DI\get;

class Runner
{
    /**
     * @var ContainerInterface|null
     */
    private ?ContainerInterface $container = null;

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
     * @throws NotFoundException|ModuleException|DependencyException
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        $this->container = ModuleContainer::buildContainer([
            WorkerState::class => new WorkerState($workerId, $server),
            Psr17Factory::class => create(Psr17Factory::class),
            ErrorHandler::class => function (LoggerInterface $logger, WorkerState $state, Config $config) {
                return ErrorHandler::create($logger, $state, $config);
            },
            ErrorManager::class => create(ErrorManager::class)
                ->constructor(get(ContainerInterface::class)),
        ]);
        $this->container->get(EventDispatcherInterface::class)->dispatch(new WorkerStart($server, $workerId));
        $this->container->get(ErrorHandler::class)->bootMessage();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response): void
    {
        $errorManager = $this->container->get(ErrorManager::class);
        $this->updateWorkerState($request, $response);
        try {
            $config = $this->container->get(Config::class);
            if ($config->has('app.name')) {
                $this->getWorkerState()->getResponse()->setHeader('Server', $config->get('app.name'));
            }
            if ($errorManager->inMaintenance()) {
                return;
            }
            $psr17Factory = $this->container->get(Psr17Factory::class);
            $requestConverter = new SwooleServerRequestConverter(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
            $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
            $requestEvent = new RequestEvent(
                $requestConverter->createFromSwoole(
                    $this->getWorkerState()->getRequest()
                )
            );
            $eventDispatcher->dispatch($requestEvent);
            if ($requestEvent->getResponse() === null) {
                $this->getLogger()?->error(
                    'No response was generated for request.',
                    [
                        'swoole_request' => $this->getWorkerState()->getRequest()->server,
                    ]
                );
                $errorManager->displayError(501);
                return;
            }
            $responseConverter = new SwooleResponseConverter(
                $this->getWorkerState()->getResponse()
            );
            $responseConverter->send($requestEvent->getResponse());
        } catch (\Throwable $e) {
            $this->getLogger()?->log(
                LogLevel::ALERT,
                sprintf(
                    'Uncaught Exception %s: "%s" at %s line %s',
                    Utils::getClass($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                ['exception' => $e]
            );
            $errorManager->displayError(500);
        }
    }

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        $this->container->get(EventDispatcherInterface::class)->dispatch(new WorkerStop($server, $workerId));
        $this->container = null;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function updateWorkerState(Request $request, Response $response): void
    {
        $state = $this->container->get(WorkerState::class);
        $state->setRequest($request);
        $state->setResponse($response);
    }

    /**
     * @return WorkerState
     */
    protected function getWorkerState(): WorkerState
    {
        return $this->container->get(WorkerState::class);
    }

    /**
     * @return LoggerInterface|null
     */
    protected function getLogger(): ?LoggerInterface
    {
        if (!$this->container?->has(LoggerInterface::class)) {
            return null;
        }
        return $this->container->get(LoggerInterface::class);
    }
}
