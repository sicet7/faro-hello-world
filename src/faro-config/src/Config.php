<?php

namespace Sicet7\Faro\Config;

use FilesystemIterator;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sicet7\Faro\Config\Definitions\VariableDefinitionInterface;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;
use SplFileInfo;

class Config implements ContainerInterface
{
    public const DELIMITER = '.';
    private const TRIM = " \t\n\r\0\x0B" . self::DELIMITER;

    /**
     * @var array
     */
    private array $config;

    /**
     * Config constructor.
     * @param array $config
     * @throws ConfigException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->makeItemReferences($this->config, $this->config);
        $this->resolveConfigVariables($this->config);
        $this->config = $this->dereferenceArray($this->config);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws ConfigException|ConfigNotFoundException
     */
    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            return $this->config[$this->parseId($id)];
        }
        throw new ConfigNotFoundException('Key: "' . $id . '" not found');
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        try {
            return array_key_exists($this->parseId($id), $this->config);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @param string $id
     * @return string
     * @throws ConfigException
     */
    protected function parseId(string $id): string
    {
        if (!is_string($id)) {
            throw new ConfigException('Config Id must be a string');
        }
        $id = trim($id, static::TRIM);
        return strtr($id, static::DELIMITER . static::DELIMITER, static::DELIMITER);
    }

    /**
     * @param array $items
     * @param array $topLevelArray
     * @param string|null $key
     * @return void
     * @throws ConfigException
     */
    protected function makeItemReferences(array &$items, array &$topLevelArray, string $key = null): void
    {
        foreach ($items as $itemKey => &$item) {
            $cKey = ($key !== null ? $key . '.' . $itemKey : $itemKey);
            if (is_array($item)) {
                $this->makeItemReferences($item, $topLevelArray, $cKey);
            }
            if (!array_key_exists($cKey, $topLevelArray)) {
                $topLevelArray[$cKey] = &$item;
            }
        }
    }

    /**
     * @param array $mainArray
     * @return array
     */
    protected function dereferenceArray(array $mainArray): array
    {
        $returnArray = [];
        foreach ($mainArray as $key => $value) {
            if (is_array($value)) {
                $returnArray[$key] = $this->dereferenceArray($value);
            } else {
                $returnArray[$key] = $value;
            }
        }
        return $returnArray;
    }

    /**
     * @param array $config
     * @return void
     * @throws ConfigException
     */
    protected function resolveConfigVariables(array &$config): void
    {
        foreach ($config as $key => $value) {
            if ($value instanceof VariableDefinitionInterface) {
                $config[$key] = $value->resolve($this);
            }
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param string $fileOrDir
     * @return array|null
     */
    public static function readFromPath(string $fileOrDir): ?array
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

                $loaded = static::readFromPath($file->getPathname());
                if (!empty($loaded)) {
                    $conf = array_merge_recursive($conf, $loaded);
                }
            }
            if (!empty($conf)) {
                return $conf;
            }
        }
        if (is_file($fileOrDir)) {
            $name = explode(static::DELIMITER, basename($fileOrDir))[0];
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
