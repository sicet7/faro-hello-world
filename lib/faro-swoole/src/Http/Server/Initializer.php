<?php

namespace Sicet7\Faro\Swoole\Http\Server;

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
    private ?Runner $runner = null;

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
        $this->runner = new Runner();
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
            // TODO: Find a way of supporting daemonized servers in the future.
        }
        if ($this->server === null) {
            throw new SwooleException('Server not yet initialized!');
        }
        if (isset($configArray['log_file']) && !file_exists($configArray['log_file'])) {
            mkdir(dirname($configArray['log_file']), 0755, true);
        }
        if (isset($configArray['document_root']) && !file_exists($configArray['document_root'])) {
            mkdir($configArray['document_root'], 0755, true);
        }
        $this->server->set($configArray);
    }

    /**
     * @throws SwooleException
     * @return void
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
