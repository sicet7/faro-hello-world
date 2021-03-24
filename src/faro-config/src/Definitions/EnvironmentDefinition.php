<?php

namespace Sicet7\Faro\Config\Definitions;

use Sicet7\Faro\Config\ConfigMap;
use Sicet7\Faro\Config\Exceptions\ConfigException;

class EnvironmentDefinition implements VariableDefinitionInterface
{
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
     * @inheritDoc
     */
    public function resolve(ConfigMap $configMap): mixed
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
        return $this->cachedValue = $this->defaultValue;
    }
}
