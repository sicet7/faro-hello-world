<?php

namespace Sicet7\Faro\Config;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Commands\ShowCommand;
use Sicet7\Faro\Console\AbstractModule;
use Sicet7\Faro\Console\Event\ListenerContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use function DI\create;
use function DI\get;

class Module extends AbstractModule
{
    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'config';
    }

    /**
     * @inheritDoc
     */
    public static function getCommandDefinitions(): array
    {
        return [
            'config:show' => ShowCommand::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getDefinitions(): array
    {
        return [
            ConfigMap::class => create(ConfigMap::class),
            ConfigLoader::class => create(ConfigLoader::class)
                ->constructor(get(ConfigMap::class)),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function setup(ContainerInterface $container): void
    {
        /** @var Application $application */
        /** @var ListenerContainerInterface $listenerContainer */
        $application = $container->get(Application::class);
        $listenerContainer = $container->get(ListenerContainerInterface::class);

        $listenerContainer->addListener(
            ConsoleCommandEvent::class,
            ConfigLoader::class,
            'config.loader'
        );

        $application->getDefinition()->addOption(
            new InputOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path to configuration directory or file("ini" or "php" file)',
                []
            )
        );
    }
}
