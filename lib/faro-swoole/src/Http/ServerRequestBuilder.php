<?php

namespace Sicet7\Faro\Swoole\Http;

use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Psr\Http\Message\ServerRequestInterface;
use Sicet7\Faro\Swoole\Http\Server\WorkerState;

class ServerRequestBuilder implements ServerRequestBuilderInterface
{
    /**
     * @var WorkerState
     */
    private WorkerState $workerState;

    /**
     * @var SwooleServerRequestConverter
     */
    private SwooleServerRequestConverter $swooleServerRequestConverter;

    /**
     * @param WorkerState $workerState
     * @param SwooleServerRequestConverter $swooleServerRequestConverter
     */
    public function __construct(
        WorkerState $workerState,
        SwooleServerRequestConverter $swooleServerRequestConverter
    ) {
        $this->workerState = $workerState;
        $this->swooleServerRequestConverter = $swooleServerRequestConverter;
    }

    /**
     * @return ServerRequestInterface
     */
    public function build(): ServerRequestInterface
    {
        return $this->swooleServerRequestConverter->createFromSwoole($this->workerState->getRequest());
    }
}
