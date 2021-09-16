<?php

namespace Sicet7\Faro\Slim;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Sicet7\Faro\Core\AbstractModule;
use Invoker\CallableResolver as PHPDICallableResolver;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Event\ListenerInterface;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Slim\Factories\ControllerInvocationStrategyFactory;
use Sicet7\Faro\Slim\Factories\ApplicationFactory;
use Sicet7\Faro\Slim\Interfaces\ApplicationFactoryInterface;
use Sicet7\Faro\Slim\Interfaces\ControllerInvocationStrategyFactoryInterface;
use Sicet7\Faro\Slim\Listeners\RequestListener;
use Slim\App;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Routing\RouteCollector;

use function DI\create;
use function DI\factory;
use function DI\get;

class Module extends AbstractModule
{

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'slim';
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'web',
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
            RequestListener::class => create(RequestListener::class)
                ->constructor(get(App::class)),
            RouteCollectorInterface::class => create(RouteCollector::class)
                ->constructor(
                    get(ResponseFactoryInterface::class),
                    get(CallableResolverInterface::class),
                    get(ContainerInterface::class),
                    get(InvocationStrategyInterface::class)
                ),
            PHPDICallableResolver::class => create(PHPDICallableResolver::class)
                ->constructor(get(ContainerInterface::class)),
            ControllerInvocationStrategyFactoryInterface::class =>
                create(ControllerInvocationStrategyFactory::class)
                    ->constructor(get(ContainerInterface::class)),
            InvocationStrategyInterface::class =>
                factory([ControllerInvocationStrategyFactoryInterface::class, 'create']),
            CallableResolverInterface::class => create(CallableResolver::class)
                ->constructor(get(PHPDICallableResolver::class))
        ];
    }

    /**
     * @param ContainerInterface $container
     */
    public static function setup(ContainerInterface $container): void
    {
        /*if ($container->has(ListenerContainerInterface::class)) {
            $listenerContainer = $container->get(ListenerContainerInterface::class);
            foreach ($container->get(ModuleList::class)->getLoadedModules() as $moduleFqn) {
                foreach ($moduleFqn::getDefinitions() as $fqn => $factory) {
                    if (is_subclass_of($fqn, ListenerInterface::class)) {
                        $listenerContainer->addListener($fqn);
                    }
                }
            }
        }*/
    }
}
