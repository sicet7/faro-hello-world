<?php

namespace Sicet7\Faro\Config\Definitions;

use Sicet7\Faro\Config\ConfigMap;
use Sicet7\Faro\Config\Exceptions\ConfigException;

interface VariableDefinition
{
    /**
     * @param ConfigMap $configMap
     * @return mixed
     * @throws ConfigException
     */
    public function resolve(ConfigMap $configMap): mixed;
}
