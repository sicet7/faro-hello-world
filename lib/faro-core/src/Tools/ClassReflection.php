<?php

namespace Sicet7\Faro\Core\Tools;

use Sicet7\Faro\Core\Attributes\Definition;

class ClassReflection
{
    /**
     * @param \ReflectionClass|string $class
     * @return string|null
     * @throws \ReflectionException
     */
    public static function getDirectory(\ReflectionClass|string $class): ?string
    {
        if (!($class instanceof \ReflectionClass)) {
            $class = new \ReflectionClass($class);
        }
        return ($fileName = $class->getFileName()) === false ?
            null :
            dirname($fileName);
    }

    /**
     * @param \ReflectionClass|string $class
     * @return string|null
     * @throws \ReflectionException
     */
    public static function getNamespace(\ReflectionClass|string $class): ?string
    {
        if (!($class instanceof \ReflectionClass)) {
            $class = new \ReflectionClass($class);
        }
        if (!$class->inNamespace()) {
            return null;
        }
        return $class->getNamespaceName();
    }

    /**
     * @param \ReflectionClass|string $class
     * @param string $property
     * @return mixed
     * @throws \ReflectionException
     */
    public static function readStaticProperty(\ReflectionClass|string $class, string $property): mixed
    {
        if (!($class instanceof \ReflectionClass)) {
            $class = new \ReflectionClass($class);
        }
        return $class->getStaticPropertyValue($property, null);
    }

    /**
     * @param \ReflectionClass|string $class
     * @param string|array $attributes
     * @return \ReflectionAttribute[]
     * @throws \ReflectionException
     */
    public static function getAttributes(\ReflectionClass|string $class, string|array $attributes = []): array
    {
        if (!($class instanceof \ReflectionClass)) {
            $class = new \ReflectionClass($class);
        }
        $output = [];
        if (!empty($attributes)) {
            if (is_string($attributes)) {
                $attributes = [$attributes];
            }
            foreach ($attributes as $attribute) {
                $output = array_merge($output, $class->getAttributes($attribute));
            }
        } else {
            return $class->getAttributes();
        }
        return $output;
    }

    /**
     * @param \ReflectionClass|string $class
     * @param string|array $attributes
     * @return bool
     * @throws \ReflectionException
     */
    public static function hasAttribute(\ReflectionClass|string $class, string|array $attributes): bool
    {
        if (!($class instanceof \ReflectionClass)) {
            $class = new \ReflectionClass($class);
        }
        return !empty(self::getAttributes($class, $attributes));
    }
}
