<?php

namespace Server\Modules\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;
use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Core\BaseModule;

use function DI\create;
use function DI\get;

class CoreModule extends BaseModule implements HasConfigInterface
{
    /**
     * @return array
     */
    public static function getConfigPaths(): array
    {
        return [
            dirname(__DIR__, 2) . '/config',
        ];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Config\Module::class,
            \Sicet7\Faro\Log\Module::class,
        ];
    }

    /**
     * @param ContainerInterface $container
     * @return void
     * @throws ConfigNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function setup(ContainerInterface $container): void
    {
        /** @var Logger $monolog */
        /** @var Config $config */
        $monolog = $container->get(Logger::class);
        $config = $container->get(Config::class);
        if (!file_exists($logDir = $config->get('dir.log'))) {
            mkdir($logDir, 0755, true);
        }
        $monolog->pushHandler(new StreamHandler(
            $logDir . '/system.log',
            Logger::DEBUG,
            true,
            null,
            true
        ));
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            Environment::class => create(Environment::class)
                ->constructor(get(Config::class)),
        ];
    }
}
