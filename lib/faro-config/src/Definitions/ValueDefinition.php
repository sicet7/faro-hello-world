<?php

namespace Sicet7\Faro\Config\Definitions;

use Psr\Container\ContainerInterface;

class ValueDefinition implements VariableDefinitionInterface
{
    /**
     * @var string
     */
    private string $configPath;

    /**
     * ValueDefinition constructor.
     * @param string $configPath
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * @param ContainerInterface $container
     * @return mixed
     */
    public function resolve(ContainerInterface $container): mixed
    {
        return $container->get($this->configPath);
    }
}
