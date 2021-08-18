<?php

namespace Sicet7\Faro\Swoole\Http;

use Sicet7\Faro\Config\ConfigMap;
use Swoole\Http\Server;

class ServerRunner
{
    public const CONFIG_KEY = 'swoole';

    /**
     * @var Server
     */
    private Server $server;

    /**
     * ServerRunner constructor.
     * @param string $ip
     * @param int $port
     * @param bool $ssl
     */
    public function __construct(string $ip, int $port, bool $ssl = false)
    {
        $socketType = SWOOLE_SOCK_TCP;
        if ($ssl) {
            $socketType = $socketType | SWOOLE_SSL;
        }
        $this->server = new Server($ip, $port, SWOOLE_PROCESS, $socketType);
    }

    /**
     * @param ConfigMap $configMap
     */
    public function configure(ConfigMap $configMap): void
    {
        $config = [];
        $custom = $configMap->readMap();
        if ($configMap->has(self::CONFIG_KEY)) {
            $config = $configMap->get(self::CONFIG_KEY);
            if (isset($custom[self::CONFIG_KEY])) {
                unset($custom[self::CONFIG_KEY]);
            }
            $config['daemonize'] = 0;
            // TODO: Find a way of supporting daemonized servers in the future.
        }
        $config['custom'] = $custom;
        $this->server->set($config);
    }
}
