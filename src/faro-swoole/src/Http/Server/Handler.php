<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Sicet7\Faro\Config\ConfigMap;
use Sicet7\Faro\Swoole\Exceptions\SwooleException;
use Swoole\Http\Server;

class Handler
{
    public const CONFIG_KEY = 'swoole';

    /**
     * @var ?Server
     */
    private ?Server $server = null;

    /**
     * @var Runner|null
     */
    private ?Runner $runner = null;

    /**
     * @param string $ip
     * @param int $port
     * @param bool $ssl
     */
    public function init(string $ip, int $port, bool $ssl = false)
    {
        $socketType = SWOOLE_SOCK_TCP;
        if ($ssl) {
            $socketType = $socketType | SWOOLE_SSL;
        }
        $this->server = new Server($ip, $port, SWOOLE_PROCESS, $socketType);
        $this->runner = new Runner();
    }

    /**
     * @param ConfigMap $configMap
     * @throws SwooleException
     */
    public function configure(ConfigMap $configMap): void
    {
        $config = [];
        if ($configMap->has(self::CONFIG_KEY)) {
            $config = $configMap->get(self::CONFIG_KEY);
            $config['daemonize'] = 0;
            // TODO: Find a way of supporting daemonized servers in the future.
        }
        if ($this->server === null) {
            throw new SwooleException('Server not yet initialized!');
        }
        $this->server->set($config);
        $this->runner->setConfig($configMap->readMap());
    }

    /**
     * @throws SwooleException
     */
    public function start(): void
    {
        if ($this->server === null) {
            throw new SwooleException('Server not yet initialized!');
        }
        $this->server->on('Start', [$this->runner, 'onStart']);
        $this->server->on('Shutdown', [$this->runner, 'onShutdown']);
        $this->server->on('WorkerStart', [$this->runner, 'onWorkerStart']);
        $this->server->on('Request', [$this->runner, 'onRequest']);
        $this->server->on('WorkerStop', [$this->runner, 'onWorkerStop']);
        $this->server->start();
    }
}
