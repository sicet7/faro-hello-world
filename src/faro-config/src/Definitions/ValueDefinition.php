<?php

namespace Sicet7\Faro\Config\Definitions;

use Sicet7\Faro\Config\ConfigMap;
use Sicet7\Faro\Config\Exceptions\ConfigException;

class ValueDefinition implements VariableDefinitionInterface
{
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
     * @param ConfigMap $configMap
     * @return mixed
     */
    public function resolve(ConfigMap $configMap): mixed
    {
        return $configMap->get($this->configPath);
    }
}
