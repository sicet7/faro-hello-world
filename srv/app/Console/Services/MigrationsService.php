<?php

namespace Server\App\Console\Services;

use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\Attributes\Definition;
use Symfony\Component\Finder\Finder;

#[Definition]
class MigrationsService
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
    public function hasMigrations(): bool
    {
        if ($this->value === null) {
            $this->value = false;
            foreach ($this->config->find('db.migrations', []) as $namespace => $directory) {
                if (
                    file_exists($directory) &&
                    (new \FilesystemIterator($directory))->valid() &&
                    Finder::create()->files()->in($directory)->name('*.php')->count() > 0
                ) {
                    $this->value = true;
                    break;
                }
            }
        }
        return $this->value;
    }
}
