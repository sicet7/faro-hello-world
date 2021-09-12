<?php

namespace Sicet7\Faro\Core;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ContainerException;
use Sicet7\Faro\Core\Exception\ModuleException;

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
        self::resolveModuleList();
        return static::buildContainer($customDefinitions);
    }

    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     */
    abstract protected static function buildContainer(array $customDefinitions = []): ContainerInterface;
}
