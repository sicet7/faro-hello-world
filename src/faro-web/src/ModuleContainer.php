<?php

namespace Sicet7\Faro\Web;

use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Exception\ModuleException;

class ModuleContainer
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
        if (!is_subclass_of($moduleFqn, AbstractModule::class)) {
            throw new ModuleException('Module: "' . $moduleFqn .
                '" does not inherit from: "' . AbstractModule::class . '"');
        }
        if (in_array($moduleFqn, self::$moduleList)) {
            throw new ModuleException('Module: "' . $moduleFqn . '" is already registered.');
        }
        self::$moduleList[] = $moduleFqn;
    }
}
