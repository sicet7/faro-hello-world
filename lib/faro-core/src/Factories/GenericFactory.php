<?php

namespace Sicet7\Faro\Core\Factories;

use DI\DependencyException;
use DI\Factory\RequestedEntry;
use DI\FactoryInterface;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ParameterResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Interfaces\GenericFactoryInterface;

abstract class GenericFactory implements GenericFactoryInterface
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

    /**
     * @return callable
     */
    public static function getDefaultImplementationFactory(): callable
    {
        return function (ContainerInterface $container) {
            $resolverChain = new ResolverChain([
                0 => new AssociativeArrayResolver(),
                1 => new TypeHintContainerResolver($container),
                2 => new DefaultValueResolver(),
            ]);
            return new class ($resolverChain) extends GenericFactory {
                /**
                 * @return array
                 */
                protected function getProvidedParameters(): array
                {
                    return [];
                }

                /**
                 * @return array
                 */
                protected function getResolvedParameters(): array
                {
                    return [];
                }

                /**
                 * @return array
                 */
                protected function getClassWhitelist(): array
                {
                    return [];
                }
            };
        };
    }
}
