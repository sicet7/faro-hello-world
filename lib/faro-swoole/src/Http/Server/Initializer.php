<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use DI\FactoryInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Swoole\Exceptions\SwooleException;
use Swoole\Http\Server;

class Initializer
{
    public const CONFIG_KEY = 'swoole';

    /**
     * @var ?Server
     */
    private ?Server $server = null;

    /**
     * @var Runner|null
     */
    private ?RunnerInterface $runner = null;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $ip
     * @param int $port
     * @param bool $ssl
     * @return void
     */
    public function init(string $ip, int $port, bool $ssl = false)
    {
        $socketType = SWOOLE_SOCK_TCP;
        if ($ssl) {
            $socketType = $socketType | SWOOLE_SSL;
        }
        $this->server = new Server($ip, $port, SWOOLE_PROCESS, $socketType);
        $this->runner = $this->factory->make(RunnerInterface::class);
    }

    /**
     * @param Config $config
     * @throws SwooleException
     * @return void
     */
    public function configure(Config $config): void
    {
        $configArray = [];
        if ($config->has(self::CONFIG_KEY)) {
            $configArray = $config->get(self::CONFIG_KEY);
            $configArray['daemonize'] = 0;
            // TODO: Find a nice way of supporting daemonized servers in the future.
        }
        if ($this->getServer() === null) {
            throw new SwooleException('Server not yet initialized!');
        }
        if (isset($configArray['log_file']) && !file_exists($configArray['log_file'])) {
            mkdir(dirname($configArray['log_file']), 0755, true);
        }
        if (isset($configArray['document_root']) && !file_exists($configArray['document_root'])) {
            mkdir($configArray['document_root'], 0755, true);
        }
        $this->getServer()->set($configArray);
    }

    /**
     * @throws SwooleException
     * @return void
     */
    public function start(): void
    {
        if ($this->getServer() === null) {
            throw new SwooleException('Server not yet initialized!');
        }
        $this->getServer()->on('Start', [$this->getRunner(), 'onStart']);
        $this->getServer()->on('Shutdown', [$this->getRunner(), 'onShutdown']);
        $this->getServer()->on('WorkerStart', [$this->getRunner(), 'onWorkerStart']);
        $this->getServer()->on('Request', [$this->getRunner(), 'onRequest']);
        $this->getServer()->on('WorkerStop', [$this->getRunner(), 'onWorkerStop']);
        $this->getServer()->start();
    }

    /**
     * @return Server|null
     */
    public function getServer(): ?Server
    {
        return $this->server;
    }

    /**
     * @return RunnerInterface|null
     */
    public function getRunner(): ?RunnerInterface
    {
        return $this->runner;
    }
}
