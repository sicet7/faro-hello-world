<?php

namespace Sicet7\Faro\Config\Definitions;

use Sicet7\Faro\Config\ConfigContainer;
use Sicet7\Faro\Config\ConfigException;

class EnvironmentVariable
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
     * @param callable|null $reader
     * @return false|mixed
     * @throws ConfigException
     */
    public function resolve(callable $reader = null)
    {
        if ($this->cachedValue && !($this->cachedValue instanceof self)) {
            return $this->cachedValue;
        }

        $reader = $reader ?? 'getenv';

        $this->cachedValue = call_user_func($reader, $this->name);

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
