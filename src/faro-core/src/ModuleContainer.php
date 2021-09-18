<?php

namespace Sicet7\Faro\Core;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ContainerException;
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
        if (class_exists('App\\Module')) {
            static::tryRegisterModule('App\\Module');
        }
        foreach (self::$moduleList[self::class] as $module) {
            static::tryRegisterModule($module);
        }
        self::resolveModuleList();
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        $moduleList = static::getModuleList();
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
}
