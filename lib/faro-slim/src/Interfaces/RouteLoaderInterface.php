<?php

namespace Sicet7\Faro\Slim\Interfaces;

interface RouteLoaderInterface
{
    /**
     * @param string $routeFqn
     * @return void
     */
    public function registerRoute(string $routeFqn): void;

    /**
     * @return void
     */
    public function loadRoutes(): void;

    /**
     * @return array
     */
    public function getLoadedRoutes(): array;
}
