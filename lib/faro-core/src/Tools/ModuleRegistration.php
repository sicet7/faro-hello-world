<?php

namespace Sicet7\Faro\Core\Tools;

use Sicet7\Faro\Core\ModuleContainer;

class ModuleRegistration
{
    /**
     * @param array $moduleMap
     * @return void
     */
    public static function run(array $moduleMap): void
    {
        foreach ($moduleMap as $moduleContainer => $modules) {
            if (empty($modules)) {
                continue;
            }
            if (!class_exists($moduleContainer)) {
                echo 'Failed to find module container: "' . $moduleContainer . '".';
                exit(1);
            }
            if (
                !is_subclass_of($moduleContainer, ModuleContainer::class) &&
                $moduleContainer !== ModuleContainer::class
            ) {
                echo 'Unknown module container: "' . $moduleContainer . '" does not extend "' .
                    ModuleContainer::class . '".';
                exit(1);
            }
            if (is_string($modules)) {
                $modules = [$modules];
            }
            foreach ($modules as $module) {
                if (!$moduleContainer::tryRegisterModule($module)) {
                    echo 'Failed to register module: "' . $module . '" into container: "' . $moduleContainer . '".';
                    exit(1);
                }
            }
        }
    }
}
