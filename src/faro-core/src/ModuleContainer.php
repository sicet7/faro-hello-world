<?php

namespace Sicet7\Faro\Core;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface as PsrListenerProviderInterface;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerProvider;
use Sicet7\Faro\Core\Exception\ContainerException;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Interfaces\Event\ListenerProviderInterface;
use Sicet7\Faro\Core\Interfaces\HasListenersInterface;
use function DI\create;
use function DI\get;

abstract class ModuleContainer
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
     * @throws ModuleException|ContainerException
     */
    public static function getContainer(array $customDefinitions = []): ContainerInterface
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
            /** @var ModuleList $moduleList */
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
    }

    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     */
    abstract protected static function buildContainer(array $customDefinitions = []): ContainerInterface;
}
