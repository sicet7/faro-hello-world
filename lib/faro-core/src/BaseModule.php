<?php

namespace Sicet7\Faro\Core;

use Psr\Container\ContainerInterface;

class BaseModule
{
    /**
     * Override this method to define custom definitions in the container.
     *
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [];
    }

    /**
     * Override this method to interact with the container right after it is created.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        // Override this method to interact with the container right after it is created.
    }

    /**
     * Returns an array containing names of modules that should be loaded before loading this module.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Returns a bool value indicating whether or not the module is enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return true;
    }
}
