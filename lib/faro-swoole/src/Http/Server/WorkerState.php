<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class WorkerState
{
    /**
     * @var int
     */
    private int $id;

    /**
     * @var Server
     */
    private Server $server;

    /**
     * @var Request|null
     */
    private ?Request $request = null;

    /**
     * @var Response|null
     */
    private ?Response $response = null;

    /**
     * WorkerState constructor.
     * @param int $id
     * @param Server $server
     */
    public function __construct(int $id, Server $server)
    {
        $this->id = $id;
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @param Request|null $request
     * @return void
     */
    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * @param Response|null $response
     * @return void
     */
    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }
}
