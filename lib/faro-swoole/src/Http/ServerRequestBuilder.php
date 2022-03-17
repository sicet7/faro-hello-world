<?php

namespace Sicet7\Faro\Swoole\Http;

use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Sicet7\Faro\Swoole\Http\Server\WorkerState;

class ServerRequestBuilder implements ServerRequestBuilderInterface
{
    /**
     * @var Psr17Factory
     */
    private Psr17Factory $psr17Factory;

    /**
     * @var WorkerState
     */
    private WorkerState $workerState;

    /**
     * @param Psr17Factory $psr17Factory
     * @param WorkerState $workerState
     */
    public function __construct(
        Psr17Factory $psr17Factory,
        WorkerState $workerState
    ) {
        $this->psr17Factory = $psr17Factory;
        $this->workerState = $workerState;
    }

    /**
     * @return ServerRequestInterface
     */
    public function build(): ServerRequestInterface
    {
        return (new SwooleServerRequestConverter(
            $this->psr17Factory,
            $this->psr17Factory,
            $this->psr17Factory,
            $this->psr17Factory
        ))->createFromSwoole($this->workerState->getRequest());
    }
}
