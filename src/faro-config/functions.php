<?php

namespace Config;

use Sicet7\Faro\Config\Definitions\ConcatDefinition;
use Sicet7\Faro\Config\Definitions\EnvironmentDefinition;
use Sicet7\Faro\Config\Definitions\ValueDefinition;

if (!function_exists('Config\env')) {
    /**
     * @param string $name
     * @param null $defaultValue
     * @return EnvironmentDefinition
     */
    function env(string $name, $defaultValue = null)
    {
        return new EnvironmentDefinition($name, (func_num_args() === 2), $defaultValue);
    }
}

if (!function_exists('Config\val')) {
    /**
     * @param string $configPath
     * @return ValueDefinition
     */
    function val(string $configPath)
    {
        return new ValueDefinition($configPath);
    }
}

if (!function_exists('Config\concat')) {
    /**
     * @param mixed ...$values
     * @return ConcatDefinition
     */
    function concat(...$values)
    {
        return new ConcatDefinition($values);
    }
}
