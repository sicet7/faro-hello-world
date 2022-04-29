<?php

namespace Server\App\Web\Providers;

use Psr\Container\ContainerInterface;

class BodyParserProvider implements \ArrayAccess
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var string[]
     */
    private array $resolvables = [];

    /**
     * @var array
     */
    private array $callables = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->callables[$offset]) || isset($this->resolvables[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (isset($this->resolvables[$offset])) {
            return $this->container->get($this->resolvables[$offset]);
        }
        if (isset($this->callables[$offset])) {
            return $this->callables[$offset];
        }
        return null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_callable($value)) {
            $this->callables[$offset] = $value;
        } elseif (is_string($value) && class_exists($value) && $this->container->has($value)) {
            $this->resolvables[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if (isset($this->callables[$offset])) {
            unset($this->callables[$offset]);
        }
        if (isset($this->resolvables[$offset])) {
            unset($this->resolvables[$offset]);
        }
    }
}
