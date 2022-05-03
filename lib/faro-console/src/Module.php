<?php

namespace Sicet7\Faro\Console;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Sicet7\Faro\Console\Event\SymfonyDispatcher;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\ContainerBuilderProxy;
use Sicet7\Faro\Core\Exception\ContainerException;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

use function DI\create;
use function DI\get;

class Module extends BaseModule implements BeforeBuildInterface
{
    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Event\Module::class,
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            SymfonyEventDispatcherInterface::class => create(SymfonyDispatcher::class)
                ->constructor(get(PsrEventDispatcherInterface::class)),
            Application::class => function (
                CommandLoaderInterface $commandLoader,
                SymfonyEventDispatcherInterface $eventDispatcher
            ) {
                $app = new Application();
                $app->setCommandLoader($commandLoader);
                $app->setDispatcher($eventDispatcher);
                return $app;
            },
        ];
    }

    /**
     * @param ContainerBuilderProxy $builderProxy
     * @return void
     * @throws ContainerException
     * @throws \ReflectionException
     */
    public static function beforeBuild(ContainerBuilderProxy $builderProxy): void
    {
        $commandFactoryMapper = new CommandFactoryMapper();

        $builderProxy->runOnLoadedDependencyOrder(function (
            string $moduleFqcn
        ) use (
            $commandFactoryMapper,
            $builderProxy
        ) {
            if (is_subclass_of($moduleFqcn, HasCommandsInterface::class)) {
                foreach ($moduleFqcn::getCommands() as $name => $commandFqcn) {
                    foreach (
                        $commandFactoryMapper->mapCommand(
                            $commandFqcn,
                            (is_string($name) && !is_numeric($name) ? $name : null)
                        ) as $fqcn => $def
                    ) {
                        $builderProxy->addDefinition($fqcn, $def);
                    }
                }
            }
        });

        $builderProxy->addDefinition(CommandLoaderInterface::class, create(ContainerCommandLoader::class)
            ->constructor(get(ContainerInterface::class), $commandFactoryMapper->getMap()));
    }
}
