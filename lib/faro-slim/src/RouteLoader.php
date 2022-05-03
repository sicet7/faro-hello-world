<?php

namespace Sicet7\Faro\Slim;

use DI\FactoryInterface;
use Sicet7\Faro\Slim\Attributes\Routing\Middleware;
use Sicet7\Faro\Slim\Attributes\Routing\Route;
use Sicet7\Faro\Slim\Exceptions\RouteException;
use Sicet7\Faro\Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteGroupInterface as SlimRouteGroupInterface;
use Sicet7\Faro\Slim\Interfaces\RouteLoaderInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteInterface;

class RouteLoader implements RouteLoaderInterface
{
    /**
     * @var array
     */
    private array $routes = [];

    /**
     * @var array
     */
    private array $loadedRoutes = [];

    /**
     * @var RouteCollectorInterface
     */
    private RouteCollectorInterface $routeCollector;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * RouteLoader constructor.
     * @param RouteCollectorInterface $routeCollector
     * @param FactoryInterface $factory
     */
    public function __construct(
        RouteCollectorInterface $routeCollector,
        FactoryInterface $factory
    ) {
        $this->routeCollector = $routeCollector;
        $this->factory = $factory;
    }

    /**
     * @param string $routeFqcn
     * @throws \ReflectionException|RouteException
     * @return void
     */
    public function registerRoute(string $routeFqcn): void
    {
        if (!method_exists($routeFqcn, '__invoke')) {
            throw new RouteException(
                'Failed to register route: "' . $routeFqcn . '". Missing "__invoke" method.'
            );
        }
        $reflection = new \ReflectionClass($routeFqcn);
        $routingAttributes = [];
        $middlewares = $this->collectMiddlewares($reflection);

        foreach ($reflection->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof Route) {
                $this->validateGroup($instance);
                $routingAttributes[] = $instance;
            }
        }

        if (empty($routingAttributes)) {
            throw new RouteException(
                'Missing routing information for route: "' . $routeFqcn . '".'
            );
        }

        foreach ($routingAttributes as $routingAttribute) {
            $group = $routingAttribute->getGroupFqcn() ?? 0;
            if (!array_key_exists($group, $this->routes) || !is_array($this->routes[$group])) {
                $this->routes[$group] = [];
            }
            $this->routes[$group][] = [
                'handler' => $routeFqcn,
                'methods' => $routingAttribute->getMethods(),
                'pattern' => $routingAttribute->getPattern(),
                'middlewares' => $middlewares,
            ];
        }
    }

    /**
     * @return void
     */
    public function loadRoutes(): void
    {
        foreach ($this->routes as $groupFqcn => $routeList) {
            if ($groupFqcn === 0) {
                foreach ($routeList as $routeArray) {
                    $route = $this->routeCollector->map(
                        $routeArray['methods'],
                        $routeArray['pattern'],
                        $routeArray['handler'],
                    );
                    foreach ($routeArray['middlewares'] as $middleware) {
                        $route->add($middleware);
                    }
                    if (!in_array($routeArray['handler'], $this->loadedRoutes)) {
                        $this->loadedRoutes[] = $routeArray['handler'];
                    }
                }
            } else {
                /** @var RouteGroupInterface $groupFqcn */
                $groupPattern = $groupFqcn::getPattern();
                $groupMiddlewares = $this->collectMiddlewares($groupFqcn);
                $routeGroup = $this->routeCollector->group($groupPattern, function (
                    RouteCollectorProxyInterface $group
                ) use ($routeList) {
                    foreach ($routeList as $routeArray) {
                        $route = $group->map(
                            $routeArray['methods'],
                            $routeArray['pattern'],
                            $routeArray['handler'],
                        );
                        foreach ($routeArray['middlewares'] as $middleware) {
                            $route->add($middleware);
                        }
                    }
                });
                $this->addMiddlewares($groupMiddlewares, $routeGroup);
//                foreach ($groupMiddlewares as $middleware) {
//                    $routeGroup->add($middleware);
//                }
                foreach ($routeList as $routeArray) {
                    if (!in_array($routeArray['handler'], $this->loadedRoutes)) {
                        $this->loadedRoutes[] = $routeArray['handler'];
                    }
                }
            }
        }
    }

    /**
     * @param Route $route
     * @return void
     * @throws RouteException
     */
    private function validateGroup(Route $route): void
    {
        $group = $route->getGroupFqcn();
        if ($group !== null && !is_subclass_of($group, RouteGroupInterface::class)) {
            throw new RouteException('Unknown Route Group: "' . $group . '"');
        }
    }

    /**
     * @param Middleware[] $attributes
     * @param SlimRouteGroupInterface|RouteInterface $target
     * @return void
     */
    private function addMiddlewares(
        array $attributes,
        SlimRouteGroupInterface|RouteInterface $target
    ): void {
        foreach ($attributes as $attribute) {

        }
    }

    /**
     * @param string|\ReflectionClass $class
     * @return Middleware[]
     * @throws \ReflectionException
     */
    private function collectMiddlewares(string|\ReflectionClass $class): array
    {
        if (!($class instanceof \ReflectionClass)) {
            $class = new \ReflectionClass($class);
        }
        $output = [];
        foreach ($class->getAttributes(Middleware::class) as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof Middleware) {
                $output[] = $instance;
            }
        }
        return $output;
    }

    /**
     * @return array
     */
    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }
}
