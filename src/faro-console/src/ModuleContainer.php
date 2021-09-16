<?php

namespace Sicet7\Faro\Console;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\LoadModuleTrait;
use Sicet7\Faro\Core\ModuleContainer as BaseModuleContainer;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Core\SetupModuleTrait;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;

use function DI\create;
use function DI\get;

class ModuleContainer extends BaseModuleContainer
{
    use LoadModuleTrait;
    use SetupModuleTrait;

    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     * @throws ModuleException
     */
    protected static function buildContainer(array $customDefinitions = []): ContainerInterface
    {
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        $moduleList = self::getModuleList();
        foreach ($moduleList as $moduleName => $moduleFqn) {
            self::loadModule($moduleList, $moduleFqn, $containerBuilder, $loadedModules);
        }
        $containerBuilder->addDefinitions([
            ModuleList::class => new ModuleList($loadedModules),
        ]);

        $commandFactoryMapper = new CommandFactoryMapper();

        foreach ($loadedModules as $moduleName => $moduleFqn) {
            if (is_subclass_of($moduleFqn, HasCommandsInterface::class)) {
                foreach ($moduleFqn::getCommands() as $commandFqn) {
                    $containerBuilder->addDefinitions([
                        $commandFqn => $commandFactoryMapper->mapCommand($commandFqn),
                    ]);
                }
            }
        }

        $containerBuilder->addDefinitions([
            CommandLoaderInterface::class => create(ContainerCommandLoader::class)
                ->constructor(get(ContainerInterface::class), $commandFactoryMapper->getMap()),
        ]);

        if (!empty($customDefinitions)) {
            $containerBuilder->addDefinitions($customDefinitions);
        }

        $container = $containerBuilder->build();
        $setupModules = [];
        foreach ($loadedModules as $loadedModule) {
            self::setupModule($loadedModules, $loadedModule, $container, $setupModules);
        }
        return $container;
    }
}
