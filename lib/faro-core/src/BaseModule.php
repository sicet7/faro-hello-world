<?php

namespace Sicet7\Faro\Core;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Attributes\Definition;
use Sicet7\Faro\Core\Tools\PSR4;

class BaseModule
{
    /**
     * @var bool
     */
    protected static bool $enableAttributeDefinitions = false;

    /**
     * @param \ReflectionClass $reflection
     * @return string|null
     */
    final protected static function getClassDirectory(\ReflectionClass $reflection): ?string
    {
        return ($fileName = $reflection->getFileName()) === false ?
            null :
            dirname($fileName);
    }

    /**
     * @param \ReflectionClass $reflection
     * @return string|null
     */
    final protected static function getClassNamespace(\ReflectionClass $reflection): ?string
    {
        if (!$reflection->inNamespace()) {
            return null;
        }
        return $reflection->getNamespaceName();
    }

    /**
     * @return array
     */
    final public static function getAllDefinitions(): array
    {
        if (!static::$enableAttributeDefinitions) {
            return static::getDefinitions();
        }
        $reflection = new \ReflectionClass(static::class);
        $directory = static::getClassDirectory($reflection);
        $namespace = static::getClassNamespace($reflection);
        if ($directory === null || $namespace === null) {
            return static::getDefinitions();
        }
        unset($reflection);
        $foundDefinitions = [];
        $foundClasses = PSR4::getFQCNs($namespace, $directory);
        foreach ($foundClasses as $class) {
            if (!class_exists($class) || is_subclass_of($class, self::class)) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            $attributeArray = $reflection->getAttributes(Definition::class);
            if (empty($attributeArray)) {
                continue;
            }
            /** @var Definition $attribute */
            $attribute = $attributeArray[array_key_first($attributeArray)]->newInstance();
            $foundDefinitions = array_merge($foundDefinitions, $attribute->getDefinitions($class));
        }
        return array_merge($foundDefinitions, static::getDefinitions());
    }

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
