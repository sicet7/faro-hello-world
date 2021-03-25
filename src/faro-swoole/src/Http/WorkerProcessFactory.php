<?php

namespace Sicet7\Faro\Swoole\Http;

class WorkerProcessFactory
{
    private string $workerProcessFqn;

    public function __construct(
        string $workerProcessFqn
    ) {
        $this->workerProcessFqn = $workerProcessFqn;
    }

    public function create(): WorkerProcessInterface
    {
        return new $this->workerProcessFqn();
    }
}
