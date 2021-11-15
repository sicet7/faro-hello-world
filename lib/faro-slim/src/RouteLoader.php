<?php

namespace Sicet7\Faro\Slim;

use Sicet7\Faro\Slim\Attributes\Routing\Route;
use Sicet7\Faro\Slim\Exceptions\RouteException;
use Sicet7\Faro\Slim\Interfaces\RouteGroupInterface;
use Sicet7\Faro\Slim\Interfaces\RouteLoaderInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

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
     * RouteLoader constructor.
     * @param RouteCollectorInterface $routeCollector
     */
    public function __construct(RouteCollectorInterface $routeCollector)
    {
        $this->routeCollector = $routeCollector;
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
                'middlewares' => $routingAttribute->getMiddlewares(),
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
                $groupMiddlewares = $groupFqcn::getMiddlewares();
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
                foreach ($groupMiddlewares as $middleware) {
                    $routeGroup->add($middleware);
                }
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
     * @return array
     */
    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }
}
