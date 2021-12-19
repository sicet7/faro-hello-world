<?php

namespace Sicet7\Faro\ORM;

use Sicet7\Faro\Core\Factories\GenericFactory;
use Sicet7\Faro\ORM\Interfaces\EntityRepositoryInterface;

class EntityRepositoryFactory extends GenericFactory
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
     * @return string[]
     */
    protected function getClassWhitelist(): array
    {
        return [
            EntityRepositoryInterface::class,
        ];
    }
}
