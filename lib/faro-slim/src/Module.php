<?php

namespace Sicet7\Faro\Slim;

use DI\ContainerBuilder;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Sicet7\Faro\Core\AbstractModule;
use Invoker\CallableResolver as PHPDICallableResolver;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Sicet7\Faro\Slim\Factories\ControllerInvocationStrategyFactory;
use Sicet7\Faro\Slim\Factories\ApplicationFactory;
use Sicet7\Faro\Slim\Factories\RouteFactory;
use Sicet7\Faro\Slim\Interfaces\ApplicationFactoryInterface;
use Sicet7\Faro\Slim\Interfaces\ControllerInvocationStrategyFactoryInterface;
use Sicet7\Faro\Slim\Interfaces\HasRoutesInterface;
use Sicet7\Faro\Slim\Interfaces\RouteLoaderInterface;
use Sicet7\Faro\Slim\Listeners\RequestListener;
use Slim\App;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Routing\RouteCollector;

use function DI\create;
use function DI\factory;
use function DI\get;

class Module extends AbstractModule implements HasListenersInterface, BeforeBuildInterface
{

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-slim';
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'faro-event',
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            App::class => factory([ApplicationFactoryInterface::class, 'create']),
            ApplicationFactoryInterface::class => create(ApplicationFactory::class)
                ->constructor(get(ContainerInterface::class)),
            Psr17Factory::class => create(Psr17Factory::class),
            ResponseFactoryInterface::class => get(Psr17Factory::class),
            RouteCollector::class  => create(RouteCollector::class)
                ->constructor(
                    get(ResponseFactoryInterface::class),
                    get(CallableResolverInterface::class),
                    get(ContainerInterface::class),
                    get(InvocationStrategyInterface::class)
                ),
            RouteCollectorInterface::class => get(RouteCollector::class),
            PHPDICallableResolver::class => create(PHPDICallableResolver::class)
                ->constructor(get(ContainerInterface::class)),
            ControllerInvocationStrategyFactoryInterface::class =>
                create(ControllerInvocationStrategyFactory::class)
                    ->constructor(get(ContainerInterface::class)),
            InvocationStrategyInterface::class =>
                factory([ControllerInvocationStrategyFactoryInterface::class, 'create']),
            CallableResolverInterface::class => create(CallableResolver::class)
                ->constructor(get(PHPDICallableResolver::class)),
            RouteFactory::class => create(RouteFactory::class)
                ->constructor(create(ResolverChain::class)
                    ->constructor([
                        create(AssociativeArrayResolver::class),
                        create(TypeHintContainerResolver::class)
                            ->constructor(get(ContainerInterface::class)),
                        create(DefaultValueResolver::class),
                    ]))->method('setClassWhitelist', get('loaded.routes')),
            RouteLoader::class => create(RouteLoader::class)
                ->constructor(get(RouteCollectorInterface::class)),
            RouteLoaderInterface::class => get(RouteLoader::class),
            'loaded.routes' => function (RouteLoaderInterface $routeLoader) {
                return $routeLoader->getLoadedRoutes();
            },
        ];
    }

    /**
     * @return string[]
     */
    public static function getListeners(): array
    {
        return [
            RequestListener::class
        ];
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        $moduleList = $container->get(ModuleList::class);
        $routeLoader = $container->get(RouteLoaderInterface::class);
        foreach ($moduleList->getLoadedModules() as $loadedModule) {
            if (is_subclass_of($loadedModule, HasRoutesInterface::class)) {
                foreach ($loadedModule::getRoutes() as $routeFqcn) {
                    $routeLoader->registerRoute($routeFqcn);
                }
            }
        }
        $routeLoader->loadRoutes();
    }

    /**
     * @param ModuleList $moduleList
     * @param ContainerBuilder $containerBuilder
     * @return void
     */
    public static function beforeBuild(ModuleList $moduleList, ContainerBuilder $containerBuilder): void
    {
        foreach ($moduleList->getLoadedModules() as $loadedModule) {
            if (is_subclass_of($loadedModule, HasRoutesInterface::class)) {
                foreach ($loadedModule::getRoutes() as $routeFqcn) {
                    $containerBuilder->addDefinitions([
                        $routeFqcn => factory([RouteFactory::class, 'create']),
                    ]);
                }
            }
        }
    }
}
