<?php

namespace Sicet7\Faro\Config;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Commands\ShowCommand;
use Sicet7\Faro\Console\HasCommandDefinitions;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;

use function DI\create;
use function DI\get;

class Module extends AbstractModule implements HasCommandDefinitions
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'config';
    }

    /**
     * @return string[]
     */
    public static function getCommandDefinitions(): array
    {
        return [
            'config:show' => ShowCommand::class,
        ];
    }

    /**
     * @return array
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
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        /** @var Application $application */
        /** @var ListenerContainerInterface $listenerContainer */
        $application = $container->get(Application::class);

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
