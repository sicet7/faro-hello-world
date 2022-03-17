<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use DI\DependencyException;
use DI\NotFoundException;
use Monolog\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Swoole\Http\ErrorManager;
use Sicet7\Faro\Swoole\Http\Server\Event\WorkerStart;
use Sicet7\Faro\Swoole\Http\Server\Event\WorkerStop;
use Sicet7\Faro\Swoole\Http\ServerRequestBuilderInterface;
use Sicet7\Faro\Web\ModuleContainer;
use Sicet7\Faro\Web\RequestEvent;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class Runner implements RunnerInterface
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
        $this->makeContainer([
            WorkerState::class => new WorkerState($workerId, $server),
        ]);
        $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(new WorkerStart($server, $workerId));
        $this->getContainer()->get(ErrorHandler::class)->bootMessage();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response): void
    {
        try {
            $errorManager = $this->getContainer()->get(ErrorManager::class);
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $exception) {
            $response->status(500, 'Internal Server Error');
            $response->end('ErrorManager: Load failed.');
            return;
        }
        try {
            $this->updateWorkerState($request, $response);
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $exception) {
            $response->status(500, 'Internal Server Error');
            $response->end('WorkerState: Update failed.');
            return;
        }
        try {
            $config = $this->getContainer()->get(Config::class);
            if ($config->has('app.name')) {
                $this->getWorkerState()->getResponse()->setHeader('Server', $config->get('app.name'));
            }
            if ($errorManager->inMaintenance()) {
                return;
            }
            /** @var ServerRequestBuilderInterface $requestBuilder */
            $requestBuilder = $this->getContainer()->get(ServerRequestBuilderInterface::class);
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->getContainer()->get(EventDispatcherInterface::class);
            /** @var ResponseEmitterInterface $responseEmitter */
            $responseEmitter = $this->getContainer()->get(ResponseEmitterInterface::class);
            $requestEvent = new RequestEvent($requestBuilder->build());
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
            $responseEmitter->emit($requestEvent->getResponse());
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
        $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(new WorkerStop($server, $workerId));
        $this->unsetContainer();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function updateWorkerState(Request $request, Response $response): void
    {
        $state = $this->getWorkerState();
        $state->setRequest($request);
        $state->setResponse($response);
    }

    /**
     * @return WorkerState
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getWorkerState(): WorkerState
    {
        return $this->getContainer()->get(WorkerState::class);
    }

    /**
     * @return LoggerInterface|null
     */
    protected function getLogger(): ?LoggerInterface
    {
        if (!$this->getContainer()?->has(LoggerInterface::class)) {
            return null;
        }
        return $this->getContainer()->get(LoggerInterface::class);
    }

    /**
     * @param array $definitions
     * @return void
     */
    protected function makeContainer(array $definitions = []): void
    {
        $this->container = ModuleContainer::buildContainer($definitions);
    }

    /**
     * @return ContainerInterface|null
     */
    protected function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return void
     */
    protected function unsetContainer(): void
    {
        $this->container = null;
    }
}
