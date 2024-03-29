<?php

namespace Sicet7\Faro\Event;

use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\BuildLock;
use Sicet7\Faro\Core\ContainerBuilderProxy;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Event\Factories\ListenerFactory;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Sicet7\Faro\Event\Interfaces\ListenerProviderInterface;
use Psr\EventDispatcher\ListenerProviderInterface as PsrListenerProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;

use function DI\create;
use function DI\factory;
use function DI\get;

class Module extends BaseModule implements BeforeBuildInterface
{
    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            ListenerFactory::class => create(ListenerFactory::class)
                ->constructor(create(ResolverChain::class)
                    ->constructor([
                        create(AssociativeArrayResolver::class),
                        create(TypeHintContainerResolver::class)
                            ->constructor(get(ContainerInterface::class)),
                        create(DefaultValueResolver::class),
                    ])),
            ListenerProvider::class => create(ListenerProvider::class)
                ->constructor(
                    get(ContainerInterface::class),
                    get(BuildLock::class)
                ),
            ListenerProviderInterface::class => get(ListenerProvider::class),
            PsrListenerProviderInterface::class => get(ListenerProviderInterface::class),
            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
        ];
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        $loadedModules = $container->get(ModuleList::class)->getLoadedModules();
        $listenerContainer = $container->get(ListenerProviderInterface::class);
        foreach ($loadedModules as $loadedModule) {
            if (is_subclass_of($loadedModule, HasListenersInterface::class)) {
                foreach ($loadedModule::getListeners() as $listener) {
                    $listenerContainer->addListener($listener);
                }
            }
        }
    }

    /**
     * @param ContainerBuilderProxy $builderProxy
     * @return void
     * @throws \Sicet7\Faro\Core\Exception\ModuleException
     */
    public static function beforeBuild(ContainerBuilderProxy $builderProxy): void
    {
        $builderProxy->runOnLoadedDependencyOrder(function (string $moduleFqcn) use ($builderProxy) {
            if (is_subclass_of($moduleFqcn, HasListenersInterface::class)) {
                foreach ($moduleFqcn::getListeners() as $listener) {
                    $builderProxy->addDefinition($listener, factory([ListenerFactory::class, 'create']));
                }
            }
        });
    }
}
