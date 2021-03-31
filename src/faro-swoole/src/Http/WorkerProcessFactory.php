<?php

namespace Sicet7\Faro\Swoole\Http;

use Sicet7\Faro\Console\GenericFactory;

class WorkerProcessFactory extends GenericFactory
{

    /**
     * @todo: Add config and module list as provided parameters.
     * @inheritDoc
     */
    protected function getProvidedParameters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getResolvedParameters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getClassWhitelist(): array
    {
        return [
            WorkerProcessInterface::class
        ];
    }
}
