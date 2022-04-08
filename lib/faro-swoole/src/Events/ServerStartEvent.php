<?php

namespace Sicet7\Faro\Swoole\Events;

use Swoole\Http\Server;

class ServerStartEvent
{
    /**
     * @var Server
     */
    private Server $server;

    /**
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }
}
