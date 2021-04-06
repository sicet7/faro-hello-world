<?php

namespace Sicet7\Faro\Swoole\Http;

use Sicet7\Faro\Config\ConfigMap;

class ServerManager
{
    public const DEFAULT_CONFIG = [

    ];

    /**
     * @var WorkerProcessFactory
     */
    private WorkerProcessFactory $workerProcessFactory;

    /**
     * @var ConfigMap
     */
    private ConfigMap $configMap;

    public function __construct(
        WorkerProcessFactory $workerProcessFactory,
        ConfigMap $configMap
    ) {
        $this->workerProcessFactory = $workerProcessFactory;
        $this->configMap = $configMap;
    }

    public function isServerRunning(): bool
    {
        // TODO: Implement this method
    }

}
