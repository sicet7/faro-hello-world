<?php

namespace Sicet7\Faro\ORM;

use DI\FactoryInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\ORM\Configuration as ORMConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\ContainerBuilderProxy;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Event\Interfaces\ListenerProviderInterface;
use Sicet7\Faro\ORM\Interfaces\EntityRepositoryInterface;
use Sicet7\Faro\ORM\Interfaces\HasEntitiesInterface;

use function DI\create;
use function DI\factory;
use function DI\get;

class Module extends BaseModule implements BeforeBuildInterface
{
    public const ENTITY_KEY = 'faro-orm.entities';

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Event\Module::class,
            \Sicet7\Faro\Log\Module::class,
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            self::ENTITY_KEY => [],
            AttributeDriver::class => create(AttributeDriver::class)
                ->constructor(get(self::ENTITY_KEY)),
            MappingDriver::class => get(AttributeDriver::class),
            DBALConfiguration::class => get(ORMConfiguration::class),
            ORMConfiguration::class => function (
                Config $config,
                MappingDriver $mappingDriver,
                ContainerInterface $container,
                RepositoryFactory $repositoryFactory
            ) {
                $dbConfig = new ORMConfiguration();
                $dbConfig->setMetadataDriverImpl($mappingDriver);
                $dbConfig->setProxyDir($config->get('db.orm.proxyClasses.dir'));
                $dbConfig->setProxyNamespace($config->get('db.orm.proxyClasses.namespace'));
                $dbConfig->setRepositoryFactory($repositoryFactory);
                $metadataCache = $config->find('db.orm.cache.metadata');
                if (is_string($metadataCache) && !empty($metadataCache)) {
                    $dbConfig->setMetadataCache($container->get($metadataCache));
                }
                $queryCache = $config->find('db.orm.cache.query');
                if (is_string($queryCache) && !empty($queryCache)) {
                    $dbConfig->setQueryCache(
                        $container->get($queryCache)
                    );
                }
                return $dbConfig;
            },
            ContainerRepositoryFactory::class => create(ContainerRepositoryFactory::class)->constructor(
                get(ContainerInterface::class),
                get(FactoryInterface::class)
            ),
            RepositoryFactory::class => get(ContainerRepositoryFactory::class),
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
            EntityRepositoryFactory::class => create(EntityRepositoryFactory::class)
        ->constructor(create(ResolverChain::class)
            ->constructor([
                create(AssociativeArrayResolver::class),
                create(TypeHintContainerResolver::class)
                    ->constructor(get(ContainerInterface::class)),
                create(DefaultValueResolver::class),
            ])),
        ];
    }

    /**
     * @param ContainerBuilderProxy $builderProxy
     * @return void
     * @throws \Sicet7\Faro\Core\Exception\ModuleException
     */
    public static function beforeBuild(
        ContainerBuilderProxy $builderProxy
    ): void {
        $builderProxy->runOnLoadedDependencyOrder(function (string $moduleFqcn) use ($builderProxy) {
            if (!is_subclass_of($moduleFqcn, HasEntitiesInterface::class)) {
                return;
            }
            $entities = $moduleFqcn::getEntities();
            $foundEntities = [];
            foreach ($entities as $entity) {
                $attributes = (new \ReflectionClass($entity))->getAttributes(Entity::class);
                if (empty($attributes)) {
                    continue;
                }
                /** @var Entity $instance */
                $instance = $attributes[array_key_first($attributes)]->newInstance();
                if (
                    $instance->repositoryClass !== null &&
                    !$builderProxy->getModuleList()->isObjectDefined($instance->repositoryClass) &&
                    is_subclass_of($instance->repositoryClass, EntityRepositoryInterface::class)
                ) {
                    $builderProxy->addDefinition(
                        $instance->repositoryClass,
                        factory([EntityRepositoryFactory::class, 'create'])
                    );
                }
                $foundEntities[] = $entity;
            }
            $builderProxy->addDefinition(self::ENTITY_KEY, $foundEntities);
        });
    }
}
