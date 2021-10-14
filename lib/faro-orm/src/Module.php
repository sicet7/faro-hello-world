<?php

namespace Sicet7\Faro\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\ORM\Configuration as ORMConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Event\Interfaces\ListenerProviderInterface;

use function DI\create;
use function DI\get;

// TODO: Make so that entities have a registration interface that is resolved in this module.
class Module extends AbstractModule
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-orm';
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [
            'faro-event',
            'faro-log',
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            AttributeDriver::class => function () {
                return new AttributeDriver([]);
            },
            MappingDriver::class => get(AttributeDriver::class),
            DBALConfiguration::class => get(ORMConfiguration::class),
            ORMConfiguration::class => function (
                Config $config,
                MappingDriver $mappingDriver,
                ContainerInterface $container
            ) {
                $dbConfig = new ORMConfiguration();
                $dbConfig->setMetadataDriverImpl($mappingDriver);
                $dbConfig->setProxyDir($config->get('db.orm.proxyClasses.dir'));
                $dbConfig->setProxyNamespace($config->get('db.orm.proxyClasses.namespace'));
                // TODO: Set RepositoryFactory to a implementation which gets from the Container.
                //$dbConfig->setRepositoryFactory();
                if ($config->has('db.orm.cache.metadata')) {
                    $dbConfig->setMetadataCache(
                        $container->get(
                            $config->get('db.orm.cache.metadata')
                        )
                    );
                }
                if ($config->has('db.orm.cache.query')) {
                    $dbConfig->setQueryCache(
                        $container->get(
                            $config->get('db.orm.cache.query')
                        )
                    );
                }
                return $dbConfig;
            },
            DoctrineEventConverter::class => create(DoctrineEventConverter::class)->constructor(
                get(ListenerProviderInterface::class),
                get(EventDispatcherInterface::class),
            ),
            EventManager::class => get(DoctrineEventConverter::class),
            Connection::class => function (
                Config $config,
                DBALConfiguration $configuration,
                EventManager $eventManager
            ) {
                return DriverManager::getConnection(
                    $config->get('db.connection'),
                    $configuration,
                    $eventManager
                );
            },
            \Doctrine\DBAL\Driver\Connection::class => get(Connection::class),
            EntityManager::class => function (
                Connection $connection,
                ORMConfiguration $configuration,
                EventManager $eventManager
            ) {
                return EntityManager::create($connection, $configuration, $eventManager);
            },
            EntityManagerInterface::class => get(EntityManager::class),
        ];
    }
}
