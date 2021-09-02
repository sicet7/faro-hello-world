<?php

namespace Sicet7\Faro\Web;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\ModuleContainer as BaseModuleContainer;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerContainer;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\ModuleList;

use function DI\create;
use function DI\get;

class ModuleContainer extends BaseModuleContainer
{
    /**
     * @param array $customDefinitions
     * @return WebContainer
     * @throws ModuleException
     */
    public static function buildContainer(array $customDefinitions = []): ContainerInterface
    {
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder(WebContainer::class);
        $containerBuilder->useAnnotations(false);
        $containerBuilder->useAutowiring(false);
        foreach (self::getModuleList() as $moduleFqn) {
            self::loadModule($moduleFqn, $containerBuilder, $loadedModules);
        }

        $containerBuilder->addDefinitions([
            ModuleList::class => new ModuleList($loadedModules),
            ListenerContainer::class => create(ListenerContainer::class)
                ->constructor(get(ContainerInterface::class)),
            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            ListenerContainerInterface::class => get(ListenerContainer::class),
            ListenerProviderInterface::class => get(ListenerContainerInterface::class),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
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
     * @param WebContainer $container
     * @param array $setupModules
     * @param string|null $initialFqn
     * @throws ModuleException
     */
    private static function setupModule(
        string $moduleFqn,
        WebContainer $container,
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
