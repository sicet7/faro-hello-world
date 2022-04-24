<?php

namespace Server\Modules\DatabasePrepare;

use Sicet7\Faro\Config\Config;

class HasMigrationsCheck
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var bool|null
     */
    private ?bool $value = null;

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
    public function execute(): bool
    {
        if ($this->value === null) {
            $this->value = false;
            foreach ($this->config->find('db.migrations', []) as $namespace => $directory) {
                if (file_exists($directory) && (new \FilesystemIterator($directory))->valid()) {
                    $this->value = true;
                    break;
                }
            }
        }
        return $this->value;
    }
}
