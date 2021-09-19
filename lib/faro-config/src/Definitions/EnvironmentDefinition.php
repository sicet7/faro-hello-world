<?php

declare(strict_types=1);

namespace Sicet7\Faro\Config\Definitions;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Exceptions\ConfigException;

class EnvironmentDefinition implements VariableDefinitionInterface
{
    /**
     * @var mixed|EnvironmentDefinition
     */
    private mixed $cachedValue;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var bool
     */
    private bool $isOptional;

    /**
     * @var mixed
     */
    private mixed $defaultValue;

    /**
     * EnvironmentDefinition constructor.
     * @param string $name
     * @param bool $isOptional
     * @param mixed|null $defaultValue
     */
    public function __construct(
        string $name,
        bool $isOptional = true,
        mixed $defaultValue = null
    ) {
        $this->name = $name;
        $this->isOptional = $isOptional;
        $this->defaultValue = $defaultValue;
        $this->cachedValue = $this;
    }

    /**
     * @param ContainerInterface $container
     * @return mixed
     * @throws ConfigException
     */
    public function resolve(ContainerInterface $container): mixed
    {
        if (!($this->cachedValue instanceof EnvironmentDefinition)) {
            return $this->cachedValue;
        }
        $this->cachedValue = getenv($this->name);

        if ($this->cachedValue !== false) {
            return $this->cachedValue;
        }

        if (!$this->isOptional) {
            throw new ConfigException(sprintf(
                'Failed to resolve required Environment Variable "%s"',
                $this->name
            ));
        }
        if ($this->defaultValue instanceof VariableDefinitionInterface) {
            return $this->cachedValue = $this->defaultValue->resolve($container);
        }
        return $this->cachedValue = $this->defaultValue;
    }
}
