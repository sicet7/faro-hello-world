<?php

namespace Config;

use Sicet7\Faro\Config\Definitions\EnvironmentVariable;

if (!function_exists('Config\env')) {
    function env(string $name, $defaultValue = null) {
        $isOptional = (func_num_args() === 2);
        return new EnvironmentVariable($name, $isOptional, $defaultValue);
    }
}
