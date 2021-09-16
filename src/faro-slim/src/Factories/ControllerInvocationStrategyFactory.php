<?php

namespace Sicet7\Faro\Slim\Factories;

use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Slim\ControllerInvocationStrategy;
use Sicet7\Faro\Slim\Interfaces\ControllerInvocationStrategyFactoryInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class ControllerInvocationStrategyFactory implements ControllerInvocationStrategyFactoryInterface
{

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * ControllerInvocationStrategyFactory constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return InvocationStrategyInterface
     */
    public function create(): InvocationStrategyInterface
    {
        return new ControllerInvocationStrategy(
            new Invoker(
                new ResolverChain([
                    new AssociativeArrayResolver(),
                    new TypeHintContainerResolver($this->container),
                    new DefaultValueResolver(),
                ]),
                $this->container
            )
        );
    }
}
