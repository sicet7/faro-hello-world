<?php

namespace Sicet7\Faro\Config;

use RecursiveDirectoryIterator;
use FilesystemIterator;
use RecursiveIteratorIterator;
use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;
use SplFileInfo;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Sicet7\Faro\Config\Exceptions\ConfigException;

#[ListensTo(ConsoleCommandEvent::class)]
class ConfigLoader implements ListenerInterface
{
    /**
     * @var ConfigMap
     */
    private ConfigMap $configMap;

    /**
     * ConfigLoader constructor.
     * @param ConfigMap $configMap
     */
    public function __construct(ConfigMap $configMap)
    {
        $this->configMap = $configMap;
    }

    /**
     * @param object $event
     * @throws ConfigException
     * @return void
     */
    public function execute(object $event): void
    {
        $configs = [];

        $configEnv = getenv('CONFIG_PATHS');
        if ($configEnv !== false && is_string($configEnv) && !empty($configEnv)) {
            foreach (explode(',', $configEnv) as $configPath) {
                $conf = $this->loadConfig($configPath);
                if (!empty($conf)) {
                    $configs = array_merge_recursive($configs, $conf);
                }
            }
        }

        if ($event instanceof ConsoleCommandEvent) {
            $input = $event->getInput();
            if ($input->hasOption('config')) {
                foreach ($input->getOption('config') as $path) {
                    $path = match (substr($path, 0, 1)) {
                        '/' => $path,
                        default => realpath(getcwd() . '/' . $path),
                    };
                    if (!is_string($path)) {
                        continue;
                    }
                    $conf = $this->loadConfig($path);
                    if (!empty($conf)) {
                        $configs = array_merge_recursive($configs, $conf);
                    }
                }
            }
        }

        $this->configMap->buildMap($configs);
    }

    /**
     * @param string $fileOrDir
     * @return array|null
     */
    protected function loadConfig(string $fileOrDir): ?array
    {
        if (!file_exists($fileOrDir)) {
            return null;
        }
        if (is_dir($fileOrDir)) {
            $dir = new RecursiveDirectoryIterator(
                $fileOrDir,
                FilesystemIterator::KEY_AS_PATHNAME |
                FilesystemIterator::CURRENT_AS_FILEINFO |
                FilesystemIterator::SKIP_DOTS
            );
            $iter = new RecursiveIteratorIterator($dir);
            $conf = [];
            foreach ($iter as $file) {
                /** @var SplFileInfo $file */
                if (!$file->isFile()) {
                    continue;
                }

                $loaded = $this->loadConfig($file->getPathname());
                if (!empty($loaded)) {
                    $conf = array_merge_recursive($conf, $loaded);
                }
            }
            if (!empty($conf)) {
                return $conf;
            }
        }
        if (is_file($fileOrDir)) {
            $name = explode(ConfigMap::DELIMITER, basename($fileOrDir))[0];
            $extension = pathinfo($fileOrDir, PATHINFO_EXTENSION);
            $configs = match ($extension) {
                'php' => require $fileOrDir,
                'ini' => parse_ini_file($fileOrDir, true, INI_SCANNER_TYPED),
                default => null
            };

            if (!empty($configs)) {
                return [
                    $name => $configs,
                ];
            }
        }
        return null;
    }
}
