<?php

namespace Sicet7\Faro\Core;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class BuildLock
{
    /**
     * @var bool
     */
    private bool $locked = false;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function lock(): void
    {
        if (!$this->locked) {
            $this->locked = true;
            if ($this->container->has(EventDispatcherInterface::class)) {
                $this->container->get(EventDispatcherInterface::class)->dispatch($this);
            }
        }
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     * @return void
     */
    public function throwIfLocked(Throwable $throwable): void
    {
        if ($this->isLocked()) {
            throw $throwable;
        }
    }
}
