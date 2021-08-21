<?php

namespace Sicet7\Faro\Swoole\Http\Server\Event;

use Swoole\Http\Server;

abstract class WorkerEvent
{
    /**
     * @var Server
     */
    private Server $server;

    /**
     * @var int
     */
    private int $workerId;

    /**
     * WorkerStart constructor.
     * @param Server $server
     * @param int $workerId
     */
    public function __construct(Server $server, int $workerId)
    {
        $this->server = $server;
        $this->workerId = $workerId;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}
