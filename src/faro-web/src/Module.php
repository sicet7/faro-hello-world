<?php

namespace Sicet7\Faro\Web;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerProvider;
use Sicet7\Faro\Core\Interfaces\Event\ListenerContainerInterface;
use Sicet7\Faro\Core\Interfaces\Event\ListenerInterface;
use Sicet7\Faro\Core\Interfaces\HasListenersInterface;
use Sicet7\Faro\Core\ModuleList;

use function DI\create;
use function DI\get;

class Module extends AbstractModule
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'web';
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        $listenerContainer = $container->get(ListenerContainerInterface::class);
        foreach ($container->get(ModuleList::class)->getLoadedModules() as $moduleFqn) {
            if (!is_subclass_of($moduleFqn, HasListenersInterface::class)) {
                continue;
            }
            foreach ($moduleFqn::getListeners() as $listener) {
                $listenerContainer->addListener($listener);
            }
        }
    }
}
