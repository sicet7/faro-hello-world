<?php

namespace Sicet7\Faro\Swoole\Http;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

interface WorkerProcessInterface
{
    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function start(Server $server, int $workerId): void;

    /**
     * @param Request $request
     * @param Response $response
     * @param Server $server
     * @return void
     */
    public function process(Request $request, Response $response, Server $server): void;

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function stop(Server $server, int $workerId): void;
}
