<?php

namespace Sicet7\Faro\Console;

use DI\ContainerBuilder;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Sicet7\Faro\Console\Event\SymfonyDispatcher;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Exception\ContainerException;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Core\ModuleList;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

use function DI\create;
use function DI\get;

class Module extends AbstractModule implements BeforeBuildInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-console';
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'faro-event'
        ];
    }

    /*
            ListenerProvider::class => create(ListenerProvider::class)
                ->constructor(get(ContainerInterface::class)),

            ListenerProviderInterface::class => get(ListenerProvider::class),
            PsrListenerProviderInterface::class => get(ListenerProviderInterface::class),

            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
    */
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
     * @param ModuleList $moduleList
     * @param ContainerBuilder $containerBuilder
     * @return void
     * @throws ContainerException
     */
    public static function beforeBuild(ModuleList $moduleList, ContainerBuilder $containerBuilder): void
    {
        $commandFactoryMapper = new CommandFactoryMapper();

        foreach ($moduleList->getLoadedModules() as $moduleFqn) {
            if (is_subclass_of($moduleFqn, HasCommandsInterface::class)) {
                foreach ($moduleFqn::getCommands() as $commandFqn) {
                    $containerBuilder->addDefinitions([
                        $commandFqn => $commandFactoryMapper->mapCommand($commandFqn),
                    ]);
                }
            }
        }

        $containerBuilder->addDefinitions([
            CommandLoaderInterface::class => create(ContainerCommandLoader::class)
                ->constructor(get(ContainerInterface::class), $commandFactoryMapper->getMap()),
        ]);
    }
}
