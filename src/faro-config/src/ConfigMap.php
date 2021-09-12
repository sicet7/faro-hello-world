<?php

namespace Sicet7\Faro\Config;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Definitions\VariableDefinitionInterface;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;

class ConfigMap implements ContainerInterface
{
    public const DELIMITER = '.';
    private const TRIM = " \t\n\r\0\x0B" . self::DELIMITER;

    /**
     * @var mixed[]
     */
    private array $map = [];

    /**
     * @return mixed[]
     */
    public function readMap(): array
    {
        return $this->map;
    }

    /**
     * @param array $items
     * @return ConfigMap
     * @throws ConfigException
     */
    public function buildMap(array $items): ConfigMap
    {
        // this might seem inefficient but the array must be in a specific state when the resolvers are run.
        // this is to avoid resolving the same value more than once.
        $this->map = $items;
        $this->makeItemReferences($this->map);
        $this->resolveMapVariables();
        $this->map = $this->dereferenceArray($this->map);
        return $this;
    }

    /**
     * @param string $id
     * @return mixed
     * @throws ConfigException|ConfigNotFoundException
     */
    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            return $this->map[$this->parseId($id)];
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
            return array_key_exists($this->parseId($id), $this->map);
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
     * @param string|null $key
     * @throws ConfigException
     * @return void
     */
    protected function makeItemReferences(array &$items, string $key = null): void
    {
        foreach ($items as $itemKey => &$item) {
            $cKey = ($key !== null ? $key . '.' . $itemKey : $itemKey);
            if (is_array($item)) {
                $this->makeItemReferences($item, $cKey);
            }
            if (!array_key_exists($cKey, $this->map)) {
                $this->map[$cKey] = &$item;
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
     * @throws ConfigException
     * @return void
     */
    protected function resolveMapVariables(): void
    {
        foreach ($this->map as $key => $value) {
            if ($value instanceof VariableDefinitionInterface) {
                $this->map[$key] = $value->resolve($this);
            }
        }
    }
}
