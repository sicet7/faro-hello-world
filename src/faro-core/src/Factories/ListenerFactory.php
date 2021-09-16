<?php

namespace Sicet7\Faro\Core\Factories;

use Sicet7\Faro\Core\Interfaces\Event\ListenerInterface;

class ListenerFactory extends GenericFactory
{
    /**
     * @return array
     */
    protected function getProvidedParameters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getResolvedParameters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getClassWhitelist(): array
    {
        return [
            ListenerInterface::class
        ];
    }
}
