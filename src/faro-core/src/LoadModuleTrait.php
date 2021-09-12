<?php

namespace Sicet7\Faro\Core;

use DI\ContainerBuilder;
use Sicet7\Faro\Core\Exception\ModuleException;

trait LoadModuleTrait
{

    /**
     * @param string[] $moduleList
     * @param string $moduleFqn
     * @param ContainerBuilder $builder
     * @param array $loadedModules
     * @param string|null $initialFqn
     * @throws ModuleException
     * @return void
     */
    protected static function loadModule(
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
}
