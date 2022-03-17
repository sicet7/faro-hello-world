<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Ilex\SwoolePsr7\SwooleResponseConverter;
use Psr\Http\Message\ResponseInterface;

class ResponseEmitter implements ResponseEmitterInterface
{
    /**
     * @var WorkerState
     */
    private WorkerState $workerState;

    /**
     * @param WorkerState $workerState
     */
    public function __construct(WorkerState $workerState)
    {
        $this->workerState = $workerState;
    }

    /**
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        (new SwooleResponseConverter($this->workerState->getResponse()))->send($response);
    }
}
