<?php

namespace Sicet7\Faro\Core\Factories;

use DI\Definition\FactoryDefinition;
use DI\DependencyException;
use DI\Factory\RequestedEntry;
use Invoker\ParameterResolver\ParameterResolver;

final class DefaultFactory
{
    /**
     * @var ParameterResolver
     */
    private ParameterResolver $parameterResolver;

    /**
     * @param ParameterResolver $parameterResolver
     */
    public function __construct(ParameterResolver $parameterResolver)
    {
        $this->parameterResolver = $parameterResolver;
    }

    /**
     * @param RequestedEntry $entry
     * @param FactoryDefinition|null $factoryDefinition
     * @param array $parameters
     * @return mixed
     * @throws DependencyException
     */
    public function create(
        RequestedEntry $entry,
        ?FactoryDefinition $factoryDefinition = null,
        array $parameters = []
    ): mixed {
        $entryName = $entry->getName();
        try {
            if (!method_exists($entryName, '__construct')) {
                return new $entryName();
            }
            $args = $this->parameterResolver->getParameters(
                new \ReflectionMethod($entryName, '__construct'),
                $parameters,
                []
            );
            ksort($args);
            return new $entryName(...$args);
        } catch (\ReflectionException $exception) {
            throw new DependencyException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
