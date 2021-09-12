<?php

namespace Sicet7\Faro\Core;

use DI\DependencyException;
use DI\Factory\RequestedEntry;
use Invoker\ParameterResolver\ParameterResolver;

abstract class GenericFactory
{
    /**
     * @var ParameterResolver
     */
    private ParameterResolver $resolver;

    /**
     * GenericFactory constructor.
     * @param ParameterResolver $resolver
     */
    public function __construct(ParameterResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param RequestedEntry $entry
     * @return object
     * @throws DependencyException
     */
    public function create(RequestedEntry $entry): object
    {
        $entryName = $entry->getName();

        if (!$this->inWhitelist($entryName)) {
            throw new DependencyException('"' . static::class . '" cannot instantiate class "' . $entryName . '".');
        }

        try {
            $args = $this->resolver->getParameters(
                new \ReflectionMethod($entryName, '__construct'),
                $this->getProvidedParameters(),
                $this->getResolvedParameters()
            );
            ksort($args);
            return new $entryName(...$args);
        } catch (\ReflectionException $exception) {
            throw new DependencyException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @param string $fqn
     * @return bool
     */
    private function inWhitelist(string $fqn): bool
    {
        $whitelist = $this->getClassWhitelist();
        if (empty($whitelist)) {
            return true;
        }
        $return = false;
        foreach ($whitelist as $whitelistedFqn) {
            if (is_subclass_of($fqn, $whitelistedFqn)) {
                $return = true;
                break;
            }
        }
        return $return;
    }

    /**
     * @return array
     */
    abstract protected function getProvidedParameters(): array;

    /**
     * @return array
     */
    abstract protected function getResolvedParameters(): array;

    /**
     * @return array
     */
    abstract protected function getClassWhitelist(): array;
}
