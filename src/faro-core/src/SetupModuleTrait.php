<?php

namespace Sicet7\Faro\Core;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Event\ListenerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;

trait SetupModuleTrait
{

    /**
     * @param string[] $moduleList
     * @param string $moduleFqn
     * @param ContainerInterface $container
     * @param array $setupModules
     * @param string|null $initialFqn
     * @return void
     * @throws NotFoundException|DependencyException|ModuleException
     */
    protected static function setupModule(
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
