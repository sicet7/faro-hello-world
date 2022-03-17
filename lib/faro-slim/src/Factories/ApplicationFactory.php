<?php

namespace Sicet7\Faro\Slim\Factories;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Slim\Interfaces\ApplicationFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;

class ApplicationFactory implements ApplicationFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * SlimApplicationFactory constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return App
     */
    public function create(): App
    {
        $app = AppFactory::createFromContainer($this->container);
        $app->addRoutingMiddleware();
        return $app;
    }
}
