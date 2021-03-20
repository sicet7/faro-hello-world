<?php

namespace Sicet7\Faro\Config;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;

class ConfigContainer implements ContainerInterface
{
    public const DELIMITER = '.';
    private const TRIM = " \t\n\r\0\x0B" . self::DELIMITER;

    /**
     * @var mixed[]
     */
    private array $items = [];

    /**
     * @var mixed[]
     */
    private array $map = [];

    /**
     * ConfigContainer constructor.
     * @param ConfigContainer|null $configContainer
     */
    public function __construct(ConfigContainer $configContainer = null)
    {
        if ($configContainer !== null) {
            $this->setItems($configContainer->getItems());
        }
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     * @return ConfigContainer
     */
    public function setItems(array $items): ConfigContainer
    {
        $this->items = $items;
        $this->map = [];
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        $parsedId = $this->parseId($id);
        if (array_key_exists($parsedId, $this->map)) {
            return $this->map[$parsedId];
        }
        return $this->findValue(
            explode(static::DELIMITER, $parsedId),
            $this->getItems(),
            $parsedId
        );
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        try {
            $this->get($id);
            return true;
        } catch (ConfigNotFoundException $notFoundException) {
            return false;
        }
    }

    /**
     * @param string $id
     * @return string
     * @throws ConfigException
     */
    protected function parseId($id): string
    {
        if (!is_string($id)) {
            throw new ConfigException('Config Id must be a string');
        }
        $id = trim($id, static::TRIM);
        return strtr($id, static::DELIMITER . static::DELIMITER, static::DELIMITER);
    }

    /**
     * @param string[] $parts
     * @param mixed $value
     * @param string $key
     * @return mixed
     * @throws ConfigNotFoundException
     */
    protected function findValue(array $parts, $value, string $key)
    {
        if (array_key_exists($key, $this->map)) {
            return $this->map[$key];
        }
        if (empty($parts)) {
            return $this->map[$key] = $value;
        }
        $current = array_shift($parts);
        if (is_array($value) && array_key_exists($current, $value)) {
            return $this->findValue($parts, $value[$current], $key);
        }
        throw new ConfigNotFoundException("Key: \"$key\" not found");
    }
}
