<?php

namespace Sicet7\Faro\Config;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Commands\ShowCommand;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

use function DI\create;
use function DI\get;

class Module extends AbstractModule implements HasCommandsInterface, HasListenersInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-config';
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'faro-console',
        ];
    }

    /**
     * @return string[]
     */
    public static function getCommands(): array
    {
        return [
            ShowCommand::class,
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            ConfigMap::class => create(ConfigMap::class),
        ];
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        /** @var Application $application */
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

    /**
     * @return array
     */
    public static function getListeners(): array
    {
        return [
            ConfigLoader::class
        ];
    }
}
