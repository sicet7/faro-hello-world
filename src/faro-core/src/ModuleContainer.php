<?php

namespace Sicet7\Faro\Core;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ContainerException;
use Sicet7\Faro\Core\Exception\ModuleContainerException;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Interfaces\AfterBuildInterface;
use Sicet7\Faro\Core\Interfaces\AfterSetupInterface;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;

abstract class ModuleContainer
{
    /**
     * @var array
     */
    private static array $moduleList = [];

    /**
     * @var array
     */
    private static array $moduleExtensionLoaders = [];

    /**
     * @param string $moduleFqn
     * @throws ModuleException
     * @return void
     */
    public static function registerModule(string $moduleFqn): void
    {
        if (!is_subclass_of($moduleFqn, AbstractModule::class)) {
            throw new ModuleException(
                'Module: "' . $moduleFqn . '" does not inherit from: "' . AbstractModule::class . '"'
            );
        }
        if (in_array($moduleFqn, static::getModuleList())) {
            throw new ModuleException('Module: "' . $moduleFqn . '" is already registered.');
        }
        static::addModuleToList($moduleFqn);
    }

    /**
     * @param string $moduleFqn
     * @return bool
     */
    public static function tryRegisterModule(string $moduleFqn): bool
    {
        try {
            static::registerModule($moduleFqn);
            return true;
        } catch (ModuleException $exception) {
            return false;
        }
    }

    /**
     * @return array
     */
    public static function getModuleList(): array
    {
        if (!isset(self::$moduleList[static::class]) || !is_array(self::$moduleList[static::class])) {
            self::$moduleList[static::class] = [];
        }
        return self::$moduleList[static::class];
    }

    /**
     * @param string $moduleFqn
     * @return void
     */
    protected static function addModuleToList(string $moduleFqn): void
    {
        if (!isset(self::$moduleList[static::class]) || !is_array(self::$moduleList[static::class])) {
            self::$moduleList[static::class] = [];
        }
        self::$moduleList[static::class][] = $moduleFqn;
    }

    /**
     * @param array $moduleList
     * @return void
     */
    protected static function setModuleList(array $moduleList): void
    {
        self::$moduleList[static::class] = $moduleList;
    }

    /**
     * @throws ModuleException
     * @return void
     */
    private static function resolveModuleList(): void
    {
        $newModuleList = [];
        foreach (static::getModuleList() as $module) {
            if (!is_subclass_of($module, AbstractModule::class)) {
                throw new ModuleException(
                    'Module: "' . $module . '" does not inherit from: "' . AbstractModule::class . '"'
                );
            }
            $newModuleList[$module::getName()] = $module;
        }
        static::setModuleList($newModuleList);
    }

    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     * @throws DependencyException
     * @throws ModuleException
     * @throws NotFoundException
     */
    public static function buildContainer(array $customDefinitions = []): ContainerInterface
    {
        self::resolveModuleList();
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        $moduleList = self::getModuleList();
        foreach ($moduleList as $moduleName => $moduleFqn) {
            self::loadModule($moduleList, $moduleFqn, $containerBuilder, $loadedModules);
        }
        $moduleListContainer = new ModuleList($loadedModules, static::getModuleList());
        $containerBuilder->addDefinitions([
            ModuleList::class => $moduleListContainer,
        ]);

        foreach ($moduleListContainer->getLoadedModules() as $moduleFqn) {
            if (is_subclass_of($moduleFqn, BeforeBuildInterface::class)) {
                $moduleFqn::beforeBuild($moduleListContainer, $containerBuilder);
            }
        }

        if (!empty($customDefinitions)) {
            $containerBuilder->addDefinitions($customDefinitions);
        }

        $container = $containerBuilder->build();

        foreach ($moduleListContainer->getLoadedModules() as $moduleFqn) {
            if (is_subclass_of($moduleFqn, AfterBuildInterface::class)) {
                $moduleFqn::afterBuild($container);
            }
        }

        $setupModules = [];
        foreach ($moduleListContainer->getLoadedModules() as $moduleFqn) {
            self::setupModule($loadedModules, $moduleFqn, $container, $setupModules);
        }

        foreach ($moduleListContainer->getLoadedModules() as $moduleFqn) {
            if (is_subclass_of($moduleFqn, AfterSetupInterface::class)) {
                $moduleFqn::afterSetup($container);
            }
        }

        return $container;
    }

    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     * @throws ModuleException|ContainerException
     */
    /*public static function getContainer(array $customDefinitions = []): ContainerInterface
    {
        if (empty(static::getModuleList())) {
            throw new ContainerException('Could not create empty container.');
        }
        $customDefinitions = array_merge([
            ListenerProvider::class => create(ListenerProvider::class)
                ->constructor(get(ContainerInterface::class)),

            ListenerProviderInterface::class => get(ListenerProvider::class),
            PsrListenerProviderInterface::class => get(ListenerProviderInterface::class),

            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
        ], $customDefinitions);
        self::resolveModuleList();
        $container = static::buildContainer($customDefinitions);
        if ($container->has(ModuleList::class)) {
            $moduleList = $container->has(ModuleList::class);
            $listenerContainer = $container->get(ListenerProviderInterface::class);
            foreach ($moduleList->getLoadedModules() as $module) {
                if (is_subclass_of($module, HasListenersInterface::class)) {
                    foreach ($module::getListeners() as $listener) {
                        $listenerContainer->addListener($listener);
                    }
                }
            }
        }
        return $container;
    }*/

    /**
     * @param string[] $moduleList
     * @param string $moduleFqn
     * @param ContainerBuilder $builder
     * @param array $loadedModules
     * @param string|null $initialFqn
     * @throws ModuleException
     * @return void
     */
    private static function loadModule(
        array $moduleList,
        string $moduleFqn,
        ContainerBuilder $builder,
        array &$loadedModules,
        ?string $initialFqn = null
    ): void {
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
            self::loadModule($moduleList, $dependencyFqn, $builder, $loadedModules, $moduleFqn);
        }
        $definitions = $moduleFqn::getDefinitions();
        if (!empty($definitions)) {
            $builder->addDefinitions($definitions);
        }
        $loadedModules[$moduleFqn::getName()] = $moduleFqn;
    }

    /**
     * @param string[] $moduleList
     * @param string $moduleFqn
     * @param ContainerInterface $container
     * @param array $setupModules
     * @param string|null $initialFqn
     * @return void
     * @throws NotFoundException|DependencyException|ModuleException
     */
    private static function setupModule(
        array $moduleList,
        string $moduleFqn,
        ContainerInterface $container,
        array &$setupModules,
        ?string $initialFqn = null
    ): void {
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
            self::setupModule($moduleList, $dependencyFqn, $container, $setupModules, $moduleFqn);
        }
        $moduleFqn::setup($container);
        $setupModules[$moduleFqn::getName()] = $moduleFqn;
    }

    /**
     * @param string $loaderFqn
     * @return void
     * @throws ModuleContainerException
     */
    public static function registerModuleExtensionLoader(string $loaderFqn): void
    {
        if (!is_subclass_of($loaderFqn, AbstractModuleExtensionLoader::class)) {
            throw new ModuleContainerException(
                'Module Extension Loader must extend "' . AbstractModuleExtensionLoader::class . '".'
            );
        }
        if (in_array($loaderFqn, static::getModuleExtensionLoaders())) {
            throw new ModuleContainerException(
                'Module Extension Loader is already registered: "' . $loaderFqn . '".'
            );
        }
        static::addModuleExtensionLoader($loaderFqn);
    }

    /**
     * @return array
     */
    public static function getModuleExtensionLoaders(): array
    {
        if (
            !isset(self::$moduleExtensionLoaders[static::class]) ||
            !is_array(self::$moduleExtensionLoaders[static::class])
        ) {
            self::$moduleExtensionLoaders[static::class] = [];
        }
        return self::$moduleExtensionLoaders[static::class];
    }

    /**
     * @param string $loaderFqn
     * @return void
     */
    protected static function addModuleExtensionLoader(string $loaderFqn): void
    {
        if (
            !isset(self::$moduleExtensionLoaders[static::class]) ||
            !is_array(self::$moduleExtensionLoaders[static::class])
        ) {
            self::$moduleExtensionLoaders[static::class] = [];
        }
        self::$moduleExtensionLoaders[static::class][] = $loaderFqn;
    }
}
