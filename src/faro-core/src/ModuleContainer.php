<?php

namespace Sicet7\Faro\Core;

use Psr\Container\ContainerInterface;
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
     */
    public static function registerModule(string $moduleFqn): void
    {
        if (!isset(self::$moduleList[static::class]) || !is_array(self::$moduleList[static::class])) {
            self::$moduleList[static::class] = [];
        }
        if (!is_subclass_of($moduleFqn, AbstractModule::class)) {
            throw new ModuleException(
                'Module: "' . $moduleFqn . '" does not inherit from: "' . AbstractModule::class . '"'
            );
        }
        if (in_array($moduleFqn, self::$moduleList[static::class])) {
            throw new ModuleException('Module: "' . $moduleFqn . '" is already registered.');
        }
        if (array_key_exists($moduleFqn::getName(), self::$moduleList[static::class])) {
            throw new ModuleException('A module with the name: "' . $moduleFqn::getName() . '" is already registered.');
        }
        self::$moduleList[static::class][$moduleFqn::getName()] = $moduleFqn;
    }

    /**
     * @return array
     */
    public static function getModuleList(): array
    {
        return self::$moduleList[static::class];
    }

    abstract public static function buildContainer(array $customDefinitions = []): ContainerInterface;
}
