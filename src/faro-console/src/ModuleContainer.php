<?php

namespace Sicet7\Faro\Console;

use DI\ContainerBuilder;
use DI\Invoker\FactoryParameterResolver;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Sicet7\Faro\Console\Event\SymfonyDispatcher;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerContainer;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\ModuleContainer as BaseModuleContainer;
use Sicet7\Faro\Core\ModuleList;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use function DI\create;
use function DI\get;

class ModuleContainer extends BaseModuleContainer
{
    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     * @throws ModuleException
     */
    public static function buildContainer(array $customDefinitions = []): ContainerInterface
    {
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        foreach (self::getModuleList() as $moduleName => $moduleFqn) {
            self::loadModule($moduleFqn, $containerBuilder, $loadedModules);
        }
        $containerBuilder->addDefinitions([
            ModuleList::class => new ModuleList($loadedModules),
            CommandFactory::class => create(CommandFactory::class)
                ->constructor(create(ResolverChain::class)
                    ->constructor([
                        create(AssociativeArrayResolver::class),
                        create(FactoryParameterResolver::class)
                            ->constructor(get(ContainerInterface::class)),
                        create(DefaultValueResolver::class)
                    ])),
            ListenerContainer::class => create(ListenerContainer::class)
                ->constructor(get(ContainerInterface::class)),
            ListenerContainerInterface::class => get(ListenerContainer::class),
            ListenerProviderInterface::class => get(ListenerContainerInterface::class),
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
        ]);

        $commandFactoryMapper = new CommandFactoryMapper();

        foreach ($loadedModules as $moduleName => $moduleFqn) {
            if (is_subclass_of($moduleFqn, HasCommandDefinitions::class)) {
                $commandDefinitions = $moduleFqn::getCommandDefinitions();
                foreach ($commandDefinitions as $commandName => $commandFqn) {
                    if (!is_string($commandName)) {
                        throw new ModuleException(
                            'Failed to determine command name for command: "' . $commandFqn . '"'
                        );
                    }
                    if (array_key_exists($commandName, $commandFactoryMapper->getMap())) {
                        throw new ModuleException(
                            "Command name collision. Command \"$commandName\" already exists."
                        );
                    }
                    $containerBuilder->addDefinitions([
                        $commandFqn => $commandFactoryMapper->mapCommand($commandName, $commandFqn),
                    ]);
                }
            }
        }

        $containerBuilder->addDefinitions([
            CommandLoaderInterface::class => create(ContainerCommandLoader::class)
                ->constructor(get(ContainerInterface::class), $commandFactoryMapper->getMap()),
        ]);

        if (!empty($customDefinitions)) {
            $containerBuilder->addDefinitions($customDefinitions);
        }

        $container = $containerBuilder->build();
        $setupModules = [];
        foreach ($loadedModules as $loadedModule) {
            self::setupModule($loadedModule, $container, $setupModules);
        }
        return $container;
    }

    /**
     * @param string $moduleFqn
     * @param ContainerBuilder $builder
     * @param array $loadedModules
     * @param string|null $initialFqn
     * @throws ModuleException
     */
    private static function loadModule(
        string $moduleFqn,
        ContainerBuilder $builder,
        array &$loadedModules,
        ?string $initialFqn = null
    ): void {
        $moduleList = self::getModuleList();
        /** @var AbstractModule $moduleFqn */
        if (!$moduleFqn::isEnabled() || in_array($moduleFqn, $loadedModules)) {
            return;
        }
        if ($initialFqn !== null && $moduleFqn == $initialFqn) {
            throw new ModuleException('Dependency loop detected for module: "' . $moduleFqn::getName() . '".');
        }
        foreach ($moduleFqn::getDependencies() as $dependency) {
            if (!array_key_exists($dependency, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependency . '" for module: "' . $moduleFqn::getName() . '".'
                );
            }
            $dependencyFqn = $moduleList[$dependency];
            /** @var AbstractModule $dependencyFqn */
            self::loadModule($dependencyFqn, $builder, $loadedModules, $moduleFqn);
        }
        $definitions = $moduleFqn::getDefinitions();
        if (!empty($definitions)) {
            $builder->addDefinitions($definitions);
        }
        $loadedModules[$moduleFqn::getName()] = $moduleFqn;
    }

    /**
     * @param string $moduleFqn
     * @param ContainerInterface $container
     * @param array $setupModules
     * @param string|null $initialFqn
     * @throws ModuleException
     */
    private static function setupModule(
        string $moduleFqn,
        ContainerInterface $container,
        array &$setupModules,
        ?string $initialFqn = null
    ): void {
        $moduleList = self::getModuleList();
        /** @var AbstractModule $moduleFqn */
        if (!$moduleFqn::isEnabled() || in_array($moduleFqn, $setupModules)) {
            return;
        }
        if ($initialFqn !== null && $moduleFqn == $initialFqn) {
            throw new ModuleException('Dependency loop detected for module: "' . $moduleFqn::getName() . '".');
        }
        foreach ($moduleFqn::getDependencies() as $dependency) {
            if (!array_key_exists($dependency, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependency . '" for module: "' . $moduleFqn::getName() . '".'
                );
            }
            $dependencyFqn = $moduleList[$dependency];
            /** @var AbstractModule $dependencyFqn */
            self::setupModule($dependencyFqn, $container, $setupModules, $moduleFqn);
        }
        $moduleFqn::setup($container);
        $setupModules[$moduleFqn::getName()] = $moduleFqn;
    }
}
