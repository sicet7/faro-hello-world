<?php

namespace Sicet7\Faro\Core;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Event\ListenerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Web\WebContainer;

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
        if ($container->has(ListenerContainerInterface::class)) {
            $listenerContainer = $container->get(ListenerContainerInterface::class);
            foreach ($moduleFqn::getDefinitions() as $fqn => $factory) {
                if (is_subclass_of($fqn, ListenerInterface::class)) {
                    $listenerContainer->addListener($fqn);
                }
            }
        }
        $moduleFqn::setup($container);
        $setupModules[$moduleFqn::getName()] = $moduleFqn;
    }
}
