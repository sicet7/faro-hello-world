<?php

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;
use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\ORM\Interfaces\HasEntitiesInterface;

class Module extends BaseModule implements HasConfigInterface, HasEntitiesInterface
{
    /**
     * @return array
     */
    public static function getConfigPaths(): array
    {
        return [
            dirname(__DIR__) . '/config',
        ];
    }

    /**
     * @param ContainerInterface $container
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @return void
     */
    public static function setup(ContainerInterface $container): void
    {
        // TODO: there has to be a smarter way of doing this...
        /** @var Logger $monolog */
        /** @var Config $config */
        $monolog = $container->get(Logger::class);
        $config = $container->get(Config::class);
        $monolog->pushHandler(new StreamHandler(
            $config->get('dir.root') . '/var/log/system.log',
            Logger::DEBUG,
            true,
            null,
            true
        ));
    }

    /**
     * @return string[]
     */
    public static function getEntities(): array
    {
        return PSR4::getFQCNs('App\\Database\\Entities', __DIR__ . '/Database/Entities');
    }
}
