<?php

namespace Sicet7\Faro\Slim\Interfaces;

interface RouteLoaderInterface
{
    /**
     * @param string $routeFqcn
     * @return void
     */
    public function registerRoute(string $routeFqcn): void;

    /**
     * @return void
     */
    public function loadRoutes(): void;

    /**
     * @return array
     */
    public function getLoadedRoutes(): array;
}
