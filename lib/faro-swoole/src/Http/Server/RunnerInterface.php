<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

interface RunnerInterface
{
    /**
     * @return EventDispatcherInterface
     */
    public function getConsoleDispatcher(): EventDispatcherInterface;

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart(Server $server, int $workerId): void;

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response): void;

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStop(Server $server, int $workerId): void;
}
