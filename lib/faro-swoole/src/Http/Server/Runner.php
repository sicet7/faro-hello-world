<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use DI\DependencyException;
use DI\NotFoundException;
use Monolog\Logger;
use Monolog\Utils;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;
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
    public const ERRORS = [
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
    ];

    public const ERROR_DESC = [
        500 => 'The server encountered an unexpected condition that prevented it from fulfilling the request.',
        501 => 'The server does not support the functionality required to fulfill the request.',
        502 => 'The server, while acting as a gateway or proxy, received an' .
            ' invalid response from an inbound server it accessed while attempting to fulfill the request.',
        503 => 'The server is currently unable to handle the request due to a temporary' .
            ' overload or scheduled maintenance, which will likely be alleviated after some delay.',
        504 => 'The server, while acting as a gateway or proxy, did not receive a timely response' .
            ' from an upstream server it needed to access in order to complete the request.',
        505 => 'The server does not support, or refuses to support, the major version of' .
            ' HTTP that was used in the request message.',
        506 => 'The server has an internal configuration error: the chosen variant resource is configured to engage' .
            ' in transparent content negotiation itself, and is therefore not a proper end point in' .
            ' the negotiation process.',
        507 => 'The method could not be performed on the resource because the server is unable to store the' .
            ' representation needed to successfully complete the request.',
        508 => 'The server terminated an operation because it encountered an infinite loop while processing a' .
            ' request with "Depth: infinity". This status indicates that the entire operation failed.',
    ];

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
        try {
            if ($this->handleMaintenance($request, $response)) {
                return;
            }
            $this->updateWorkerState($request, $response);
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
                $this->sendServerError(501);
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
            $this->sendServerError(500);
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
     * @param int $code
     * @return void
     */
    protected function sendServerError(int $code): void
    {
        if (!array_key_exists($code, self::ERRORS)) {
            $this->getWorkerState()->getResponse()->setStatusCode(500, self::ERRORS[500]);
            $this->getWorkerState()->getResponse()->end(self::ERROR_DESC[500]);
            return;
        }
        $this->getWorkerState()->getResponse()->setStatusCode($code, self::ERRORS[$code]);
        $this->getWorkerState()->getResponse()->end(self::ERROR_DESC[$code]);
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

    /**
     * @param Request $request
     * @param Response $response
     * @return bool Returns true if maintenance is enabled and has been handled, false otherwise.
     * @throws ConfigException
     * @throws ConfigNotFoundException
     */
    protected function handleMaintenance(Request $request, Response $response): bool
    {
        // TODO: move this handler to its own class to expand functionality with cookies and more.
        /** @var Config $config */
        $config = $this->container->get(Config::class);
        $appRoot = $config->get('dir.root');
        if (file_exists($appRoot . '/maintenance.flag')) {
            if ($config->has('maintenance.whitelist.ips')) {
                $whitelistedIps = $config->get('maintenance.whitelist.ips');
                if (in_array($request->server['remote_addr'], $whitelistedIps)) {
                    return false;
                }
            }
            $response->setStatusCode(503, self::ERRORS[503]);
            $response->end(file_get_contents($appRoot . '/maintenance.flag'));
            return true;
        }
        return false;
    }
}
