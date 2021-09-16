<?php

namespace Sicet7\Faro\Console;

use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface as PsrListenerProviderInterface;
use Sicet7\Faro\Console\Event\SymfonyDispatcher;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerProvider;
use Sicet7\Faro\Core\Interfaces\Event\ListenerProviderInterface;
use Sicet7\Faro\Core\Interfaces\HasListenersInterface;
use Sicet7\Faro\Core\ModuleList;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

use function DI\create;
use function DI\get;

class Module extends AbstractModule
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'console';
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            CommandFactory::class => create(CommandFactory::class)
                ->constructor(create(ResolverChain::class)
                    ->constructor([
                        create(AssociativeArrayResolver::class),
                        create(TypeHintContainerResolver::class)
                            ->constructor(get(ContainerInterface::class)),
                        create(DefaultValueResolver::class),
                    ])),
            ListenerProvider::class => create(ListenerProvider::class)
                ->constructor(get(ContainerInterface::class)),

            ListenerProviderInterface::class => get(ListenerProvider::class),
            PsrListenerProviderInterface::class => get(ListenerProviderInterface::class),

            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
            SymfonyEventDispatcherInterface::class => create(SymfonyDispatcher::class)
                ->constructor(get(PsrEventDispatcherInterface::class)),
            Application::class => function (
                CommandLoaderInterface $commandLoader,
                SymfonyEventDispatcherInterface $eventDispatcher
            ) {
                $app = new Application();
                $app->setCommandLoader($commandLoader);
                $app->setDispatcher($eventDispatcher);
                return $app;
            },
        ];
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        $listenerContainer = $container->get(ListenerProviderInterface::class);
        foreach ($container->get(ModuleList::class)->getLoadedModules() as $moduleFqn) {
            if (!is_subclass_of($moduleFqn, HasListenersInterface::class)) {
                continue;
            }
            foreach ($moduleFqn::getListeners() as $listener) {
                $listenerContainer->addListener($listener);
            }
        }
    }
}
