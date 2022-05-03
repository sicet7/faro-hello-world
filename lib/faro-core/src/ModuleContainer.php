<?php

namespace Sicet7\Faro\Core;

use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Factories\DefaultFactory;
use Sicet7\Faro\Core\Factories\GenericFactory;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Core\Interfaces\GenericFactoryInterface;

use function DI\create;
use function DI\get;

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
        if (!is_subclass_of($moduleFqcn, BaseModule::class)) {
            throw new ModuleException(
                'Module: "' . $moduleFqcn . '" does not inherit from: "' . BaseModule::class . '"'
            );
        }
        if (in_array(trim($moduleFqcn, " \t\n\r\0\x0B\\"), static::getModuleList())) {
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
        self::$moduleList[static::class][] = trim($moduleFqcn, " \t\n\r\0\x0B\\");
    }

    /**
     * @param string $sourceModule
     * @param array $customDefinitions
     * @return ContainerInterface
     * @throws DependencyException
     * @throws ModuleException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function buildContainer(string $sourceModule, array $customDefinitions = []): ContainerInterface
    {
        foreach (self::$moduleList[self::class] as $module) {
            static::tryRegisterModule($module);
        }
        $loadedModules = [];
        $definedObjects = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        $moduleList = static::getModuleList();
        foreach ($moduleList as $moduleFqcn) {
            self::runCallableOnDependencyOrder(
                $moduleList,
                $moduleFqcn,
                function (string $moduleFqcn) use (&$definedObjects, $containerBuilder) {
                    $definitions = $moduleFqcn::getAllDefinitions();
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

        $definedObjects[BuildLock::class] = BaseModule::class;

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

        $definedObjects[ModuleList::class] = BaseModule::class;

        if (!empty($customDefinitions)) {
            foreach (array_filter(array_keys($customDefinitions), 'is_string') as $def) {
                $definedObjects[$def] = $sourceModule;
            }
            $containerBuilder->addDefinitions($customDefinitions);
        }

        $containerBuilder->addDefinitions([
            ModuleList::class => new ModuleList(
                $loadedModules,
                $moduleList,
                $definedObjects
            ),
            DefaultFactory::class => function (ContainerInterface $container) {
                return new DefaultFactory(new ResolverChain([
                    0 => new AssociativeArrayResolver(),
                    1 => new TypeHintContainerResolver($container),
                    2 => new DefaultValueResolver(),
                ]));
            },
        ]);

        /** @var Container $container */
        $container = $containerBuilder->build();

        $setupModules = [];
        foreach ($loadedModules as $moduleFqcn) {
            self::runCallableOnDependencyOrder(
                $loadedModules,
                $moduleFqcn,
                function (string $moduleFqcn) use ($container) {
                    if (method_exists($moduleFqcn, 'setup')) {
                        $container->call([$moduleFqcn, 'setup']);
                    }
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
        $moduleFqcn = trim($moduleFqcn, " \t\n\r\0\x0B\\");
        /** @var BaseModule $moduleFqcn */
        if (!$moduleFqcn::isEnabled() || in_array($moduleFqcn, $alreadyRan)) {
            return;
        }
        if ($initialFqcn !== null && $moduleFqcn == $initialFqcn) {
            throw new ModuleException('Dependency loop detected for module: "' . $moduleFqcn . '".');
        }
        if ($initialFqcn === null) {
            $initialFqcn = $moduleFqcn;
        }
        foreach ($moduleFqcn::getDependencies() as $dependencyFqcn) {
            if (!in_array($dependencyFqcn, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependencyFqcn . '" for module: "' . $moduleFqcn . '".'
                );
            }
            /** @var BaseModule $dependencyFqcn */
            self::runCallableOnDependencyOrder(
                $moduleList,
                $dependencyFqcn,
                $callable,
                $alreadyRan,
                $initialFqcn
            );
        }
        $callable($moduleFqcn);
        $alreadyRan[] = $moduleFqcn;
    }
}
