<?php

namespace Server\App\Core\Services;

use Sicet7\Faro\Config\Config;

class EnvironmentService
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isProduction(): bool
    {
        return str_contains($this->config->find('app.env', 'production'), 'prod');
    }

    /**
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return str_contains($this->config->find('app.env', 'production'), 'dev');
    }
}
