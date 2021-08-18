<?php

namespace Sicet7\Faro\Swoole\Http;

use Sicet7\Faro\Config\ConfigMap;

class ServerManager
{
    public const CONFIG_KEY = 'swoole';

    /**
     * @var array|mixed|string
     */
    private array $swooleConfig = [];

    /**
     * ServerManager constructor.
     * @param ConfigMap $configMap
     */
    public function __construct(
        ConfigMap $configMap
    ) {
        if ($configMap->has(self::CONFIG_KEY)) {
            $this->swooleConfig = $configMap->get(self::CONFIG_KEY);
        }
    }

    public function runServer(string $ip, int $port)
    {
        $config = $this->swooleConfig;
        // TODO: Find a way of supporting daemonized servers in the future.
        $config['daemonize'] = 0;

    }

}
