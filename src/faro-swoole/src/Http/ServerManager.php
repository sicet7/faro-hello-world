<?php

namespace Sicet7\Faro\Swoole\Http;

class ServerManager
{
    /**
     * @var WorkerProcessFactory
     */
    private WorkerProcessFactory $workerProcessFactory;

    public function __construct(
        WorkerProcessFactory $workerProcessFactory
    ) {
        $this->workerProcessFactory = $workerProcessFactory;
    }


}
