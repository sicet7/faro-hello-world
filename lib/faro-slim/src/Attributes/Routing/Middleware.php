<?php

namespace Sicet7\Faro\Slim\Attributes\Routing;

use Attribute;
use DI\FactoryInterface;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @var string
     */
    private string $middlewareFQCN;

    /**
     * @var bool
     */
    private bool $sharedInstance;

    /**
     * @var array
     */
    private array $parameters;

    /**
     * @param string $middlewareFQCN
     * @param bool $sharedInstance
     * @param array $parameters
     */
    public function __construct(string $middlewareFQCN, bool $sharedInstance = true, array $parameters = [])
    {
        $this->middlewareFQCN = $middlewareFQCN;
        $this->parameters = $parameters;
        $this->sharedInstance = $sharedInstance;
    }

    /**
     * @param RouteInterface $route
     * @param FactoryInterface $factory
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function register(
        RouteInterface $route,
        FactoryInterface $factory
    ): void {
        if ($this->sharedInstance) {
            $route->add($this->middlewareFQCN);
        } else {
            $route->add($factory->make($this->middlewareFQCN, $this->parameters));
        }
    }

    /**
     * @return string
     */
    public function getMiddlewareFQCN(): string
    {
        return $this->middlewareFQCN;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function isSharedInstance(): bool
    {
        return $this->sharedInstance;
    }
}
