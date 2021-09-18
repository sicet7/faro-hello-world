<?php

namespace Sicet7\Faro\Config\Definitions;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Exceptions\ConfigException;

interface VariableDefinitionInterface
{
    /**
     * @param ContainerInterface $container
     * @return mixed
     * @throws ConfigException
     */
    public function resolve(ContainerInterface $container): mixed;
}
