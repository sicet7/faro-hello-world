<?php

namespace Sicet7\Faro\Core;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;

use function DI\create;
use function DI\get;

class ModuleContainer
{
    private const NAME = 'core';

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
        $definedObjects = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        $moduleList = static::getModuleList();
        foreach ($moduleList as $moduleName => $moduleFqcn) {
            self::runCallableOnDependencyOrder(
                $moduleList,
                $moduleFqcn,
                function (string $moduleFqcn) use (&$definedObjects, $containerBuilder) {
                    $definitions = $moduleFqcn::getDefinitions();
                    if (!empty($definitions)) {
                        foreach (array_keys($definitions) as $definitionFqcn) {
                            if (!is_string($definitionFqcn)) {
                                continue;
                            }
                            $definedObjects[$definitionFqcn] = $moduleFqcn;
                        }
                        $containerBuilder->addDefinitions($definitions);
                    }
                },
                $loadedModules
            );
        }

        $definedObjects[BuildLock::class] = self::NAME;

        $containerBuilder->addDefinitions([
            BuildLock::class => create(BuildLock::class)
                ->constructor(get(ContainerInterface::class)),
        ]);

        $beforeBuild = [];
        foreach ($loadedModules as $moduleFqcn) {
            self::runCallableOnDependencyOrder(
                $loadedModules,
                $moduleFqcn,
                function (string $moduleFqcn) use ($containerBuilder, $loadedModules, $moduleList, &$definedObjects) {
                    if (is_subclass_of($moduleFqcn, BeforeBuildInterface::class)) {
                        $containerBuilderProxy = new ContainerBuilderProxy(
                            $containerBuilder,
                            new ModuleList(
                                $loadedModules,
                                $moduleList,
                                $definedObjects
                            ),
                            $moduleFqcn
                        );
                        $moduleFqcn::beforeBuild($containerBuilderProxy);
                        $definedObjects = $containerBuilderProxy->getModuleList()->getDefinedObjects();
                    }
                },
                $beforeBuild
            );
        }

        $definedObjects[ModuleList::class] = self::NAME;

        if (!empty($customDefinitions)) {
            foreach (array_filter(array_keys($customDefinitions), 'is_string') as $def) {
                $definedObjects[$def] = self::NAME;
            }
            $containerBuilder->addDefinitions($customDefinitions);
        }

        $containerBuilder->addDefinitions([
            ModuleList::class => new ModuleList(
                $loadedModules,
                $moduleList,
                $definedObjects
            ),
        ]);

        $container = $containerBuilder->build();

        $setupModules = [];
        foreach ($loadedModules as $moduleFqcn) {
            self::runCallableOnDependencyOrder(
                $loadedModules,
                $moduleFqcn,
                function (string $moduleFqcn) use ($container) {
                    $moduleFqcn::setup($container);
                },
                $setupModules
            );
        }

        $container->get(BuildLock::class)->lock();
        return $container;
    }

    /**
     * @param array $moduleList
     * @param string $moduleFqcn
     * @param callable $callable
     * @param array $alreadyRan
     * @param string|null $initialFqcn
     * @return void
     * @throws ModuleException
     */
    final public static function runCallableOnDependencyOrder(
        array $moduleList,
        string $moduleFqcn,
        callable $callable,
        array &$alreadyRan,
        ?string $initialFqcn = null
    ): void {
        /** @var AbstractModule $moduleFqcn */
        $name = $moduleFqcn::getName();
        if (!$moduleFqcn::isEnabled() || in_array($moduleFqcn, $alreadyRan)) {
            return;
        }
        if ($initialFqcn !== null && $moduleFqcn == $initialFqcn) {
            throw new ModuleException('Dependency loop detected for module: "' . $name . '".');
        }
        if ($initialFqcn === null) {
            $initialFqcn = $moduleFqcn;
        }
        foreach ($moduleFqcn::getDependencies() as $dependency) {
            if (!array_key_exists($dependency, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependency . '" for module: "' . $name . '".'
                );
            }
            $dependencyFqcn = $moduleList[$dependency];
            /** @var AbstractModule $dependencyFqcn */
            self::runCallableOnDependencyOrder(
                $moduleList,
                $dependencyFqcn,
                $callable,
                $alreadyRan,
                $initialFqcn
            );
        }
        $callable($moduleFqcn);
        $alreadyRan[$name] = $moduleFqcn;
    }
}
