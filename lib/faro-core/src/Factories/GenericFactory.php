<?php

namespace Sicet7\Faro\Core\Factories;

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
            if (!method_exists($entryName, '__construct')) {
                return new $entryName();
            }
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
     * @param string $fqcn
     * @return bool
     */
    protected function inWhitelist(string $fqcn): bool
    {
        $whitelist = $this->getClassWhitelist();
        if (empty($whitelist)) {
            return true;
        }
        $return = false;
        foreach ($whitelist as $whitelistedFqcn) {
            if ($fqcn == $whitelistedFqcn || is_subclass_of($fqcn, $whitelistedFqcn)) {
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
