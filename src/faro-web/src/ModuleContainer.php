<?php

namespace Sicet7\Faro\Web;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Event\ListenerInterface;
use Sicet7\Faro\Core\LoadModuleTrait;
use Sicet7\Faro\Core\ModuleContainer as BaseModuleContainer;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerContainer;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Core\SetupModuleTrait;

use function DI\create;
use function DI\get;

class ModuleContainer extends BaseModuleContainer
{
    use LoadModuleTrait;
    use SetupModuleTrait;

    /**
     * @param array $customDefinitions
     * @return WebContainer
     * @throws ModuleException
     */
    protected static function buildContainer(array $customDefinitions = []): WebContainer
    {
        $loadedModules = [];
        $containerBuilder = new ContainerBuilder(WebContainer::class);
        $containerBuilder->useAnnotations(false);
        $containerBuilder->useAutowiring(false);
        $moduleList = self::getModuleList();
        foreach ($moduleList as $moduleFqn) {
            self::loadModule($moduleList, $moduleFqn, $containerBuilder, $loadedModules);
        }

        $containerBuilder->addDefinitions([
            ModuleList::class => new ModuleList($loadedModules),
            ListenerContainer::class => create(ListenerContainer::class)
                ->constructor(get(ContainerInterface::class)),
            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            ListenerContainerInterface::class => get(ListenerContainer::class),
            ListenerProviderInterface::class => get(ListenerContainerInterface::class),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
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
