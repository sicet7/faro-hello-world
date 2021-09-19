<?php

namespace Sicet7\Faro\Slim;

use Invoker\CallableResolver as PHPDICallableResolver;
use Invoker\Exception\NotCallableException;
use ReflectionException;
use Slim\Interfaces\CallableResolverInterface;

class CallableResolver implements CallableResolverInterface
{
    /**
     * @var PHPDICallableResolver
     */
    private PHPDICallableResolver $callableResolver;

    /**
     * CallableResolver constructor.
     * @param PHPDICallableResolver $callableResolver
     */
    public function __construct(PHPDICallableResolver $callableResolver)
    {
        $this->callableResolver = $callableResolver;
    }

    /**
     * @param callable|string $toResolve
     * @return callable
     * @throws NotCallableException
     * @throws ReflectionException
     */
    public function resolve($toResolve): callable
    {
        return $this->callableResolver->resolve($toResolve);
    }
}
