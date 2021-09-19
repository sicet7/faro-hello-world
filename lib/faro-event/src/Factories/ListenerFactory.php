<?php

namespace Sicet7\Faro\Event\Factories;

use Sicet7\Faro\Core\Factories\GenericFactory;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;

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
