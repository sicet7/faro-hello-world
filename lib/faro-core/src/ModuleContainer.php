<?php

namespace Sicet7\Faro\Core;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Interfaces\AfterBuildInterface;
use Sicet7\Faro\Core\Interfaces\AfterSetupInterface;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;

class ModuleContainer
{
    /**
     * @var array
     */
    private static array $moduleList = [];

    /**
     * @param string $moduleFqcn
     * @throws ModuleException
     * @return void
     */
    public static function registerModule(string $moduleFqcn): void
    {
        if (!is_subclass_of($moduleFqcn, AbstractModule::class)) {
            throw new ModuleException(
                'Module: "' . $moduleFqcn . '" does not inherit from: "' . AbstractModule::class . '"'
            );
        }
        if (in_array($moduleFqcn, static::getModuleList())) {
            throw new ModuleException('Module: "' . $moduleFqcn . '" is already registered.');
        }
        static::addModuleToList($moduleFqcn);
    }

    /**
     * @param string $moduleFqcn
     * @return bool
     */
    public static function tryRegisterModule(string $moduleFqcn): bool
    {
        try {
            static::registerModule($moduleFqcn);
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
     * @param string $moduleFqcn
     * @return void
     */
    protected static function addModuleToList(string $moduleFqcn): void
    {
        if (!isset(self::$moduleList[static::class]) || !is_array(self::$moduleList[static::class])) {
            self::$moduleList[static::class] = [];
        }
        self::$moduleList[static::class][] = $moduleFqcn;
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
            $name = $module::getName();
            if (array_key_exists($name, $newModuleList)) {
                throw new ModuleException(
                    'Module name already taken: "' . $name . '".'
                );
            }
            $newModuleList[$name] = $module;
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
        foreach (self::$moduleList[self::class] as $module) {
            static::tryRegisterModule($module);
        }
        self::resolveModuleList();
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        $moduleList = static::getModuleList();
        foreach ($moduleList as $moduleName => $moduleFqcn) {
            self::loadModule($moduleList, $moduleFqcn, $containerBuilder, $loadedModules);
        }
        $moduleListContainer = new ModuleList($loadedModules, $moduleList);
        $containerBuilder->addDefinitions([
            ModuleList::class => $moduleListContainer,
            BuildLock::class => new BuildLock(),
        ]);

        foreach ($moduleListContainer->getLoadedModules() as $moduleFqcn) {
            if (is_subclass_of($moduleFqcn, BeforeBuildInterface::class)) {
                $moduleFqcn::beforeBuild($moduleListContainer, $containerBuilder);
            }
        }

        if (!empty($customDefinitions)) {
            $containerBuilder->addDefinitions($customDefinitions);
        }

        $container = $containerBuilder->build();

        foreach ($moduleListContainer->getLoadedModules() as $moduleFqcn) {
            if (is_subclass_of($moduleFqcn, AfterBuildInterface::class)) {
                $moduleFqcn::afterBuild($container);
            }
        }

        $setupModules = [];
        foreach ($moduleListContainer->getLoadedModules() as $moduleFqcn) {
            self::setupModule($loadedModules, $moduleFqcn, $container, $setupModules);
        }

        foreach ($moduleListContainer->getLoadedModules() as $moduleFqcn) {
            if (is_subclass_of($moduleFqcn, AfterSetupInterface::class)) {
                $moduleFqcn::afterSetup($container);
            }
        }

        $container->get(BuildLock::class)->lock();
        return $container;
    }

    /**
     * @param string[] $moduleList
     * @param string $moduleFqcn
     * @param ContainerBuilder $builder
     * @param array $loadedModules
     * @param string|null $initialFqcn
     * @return void
     * @throws ModuleException
     */
    private static function loadModule(
        array $moduleList,
        string $moduleFqcn,
        ContainerBuilder $builder,
        array &$loadedModules,
        ?string $initialFqcn = null
    ): void {
        /** @var AbstractModule $moduleFqcn */
        if (!$moduleFqcn::isEnabled() || in_array($moduleFqcn, $loadedModules)) {
            return;
        }
        if ($initialFqcn !== null && $moduleFqcn == $initialFqcn) {
            throw new ModuleException('Dependency loop detected for module: "' . $moduleFqcn::getName() . '".');
        }
        if ($initialFqcn === null) {
            $initialFqcn = $moduleFqcn;
        }
        foreach ($moduleFqcn::getDependencies() as $dependency) {
            if (!array_key_exists($dependency, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependency . '" for module: "' . $moduleFqcn::getName() . '".'
                );
            }
            $dependencyFqcn = $moduleList[$dependency];
            /** @var AbstractModule $dependencyFqcn */
            self::loadModule($moduleList, $dependencyFqcn, $builder, $loadedModules, $initialFqcn);
        }
        $definitions = $moduleFqcn::getDefinitions();
        if (!empty($definitions)) {
            $builder->addDefinitions($definitions);
        }
        $loadedModules[$moduleFqcn::getName()] = $moduleFqcn;
    }

    /**
     * @param string[] $moduleList
     * @param string $moduleFqcn
     * @param ContainerInterface $container
     * @param array $setupModules
     * @param string|null $initialFqcn
     * @return void
     * @throws NotFoundException|DependencyException|ModuleException
     */
    private static function setupModule(
        array $moduleList,
        string $moduleFqcn,
        ContainerInterface $container,
        array &$setupModules,
        ?string $initialFqcn = null
    ): void {
        /** @var AbstractModule $moduleFqcn */
        if (!$moduleFqcn::isEnabled() || in_array($moduleFqcn, $setupModules)) {
            return;
        }
        if ($initialFqcn !== null && $moduleFqcn == $initialFqcn) {
            throw new ModuleException('Dependency loop detected for module: "' . $moduleFqcn::getName() . '".');
        }
        if ($initialFqcn === null) {
            $initialFqcn = $moduleFqcn;
        }
        foreach ($moduleFqcn::getDependencies() as $dependency) {
            if (!array_key_exists($dependency, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependency . '" for module: "' . $moduleFqcn::getName() . '".'
                );
            }
            $dependencyFqcn = $moduleList[$dependency];
            /** @var AbstractModule $dependencyFqcn */
            self::setupModule($moduleList, $dependencyFqcn, $container, $setupModules, $initialFqcn);
        }
        $moduleFqcn::setup($container);
        $setupModules[$moduleFqcn::getName()] = $moduleFqcn;
    }
}
