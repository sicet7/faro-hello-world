<?php

namespace Sicet7\Faro\ORM;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider;
use Doctrine\Migrations\Configuration\Configuration as MigrationConfiguration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand;
use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Console\Interfaces\HasCommandsInterface;
use Sicet7\Faro\Core\AbstractModule;

use function DI\create;
use function DI\get;

class ConsoleModule extends AbstractModule implements HasCommandsInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-orm-console';
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'faro-console',
            'faro-orm',
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            MigrationConfiguration::class => function () {
                $migrationConfiguration = new MigrationConfiguration();
                $migrationConfiguration->setMigrationOrganization(
                    MigrationConfiguration::VERSIONS_ORGANIZATION_NONE
                );
                return $migrationConfiguration;
            },
            ExistingConfiguration::class => create(ExistingConfiguration::class)->constructor(
                get(MigrationConfiguration::class),
            ),
            ConfigurationLoader::class => get(ExistingConfiguration::class),
            ExistingEntityManager::class => create(ExistingEntityManager::class)->constructor(
                get(EntityManagerInterface::class),
            ),
            DependencyFactory::class => function (
                ConfigurationLoader $configurationLoader,
                ExistingEntityManager $existingEntityManager,
                LoggerInterface $logger
            ) {
                return DependencyFactory::fromEntityManager(
                    $configurationLoader,
                    $existingEntityManager,
                    $logger
                );
            },
            SingleManagerProvider::class => create(SingleManagerProvider::class)->constructor(
                get(EntityManagerInterface::class),
            ),
            EntityManagerProvider::class => get(SingleManagerProvider::class),
            SingleConnectionProvider::class => create(SingleConnectionProvider::class)->constructor(
                get(Connection::class),
            ),
            ConnectionProvider::class => get(SingleConnectionProvider::class),
        ];
    }

    /**
     * @return string[]
     */
    public static function getCommands(): array
    {
        return [
            //DBAL
            'dbal:reserved-words' => ReservedWordsCommand::class,
            'dbal:run-sql' => RunSqlCommand::class,

            //ORM
            'orm:clear-cache:region:collection' => CollectionRegionCommand::class,
            'orm:clear-cache:region:entity' => EntityRegionCommand::class,
            'orm:clear-cache:metadata' => MetadataCommand::class,
            'orm:clear-cache:query' => QueryCommand::class,
            'orm:clear-cache:region:query' => QueryRegionCommand::class,
            'orm:clear-cache:result' => ResultCommand::class,
            'orm:schema-tool:create' => CreateCommand::class,
            'orm:schema-tool:update' => UpdateCommand::class,
            'orm:schema-tool:drop' => DropCommand::class,
            'orm:ensure-production-settings' => EnsureProductionSettingsCommand::class,
            'orm:generate-proxies' => GenerateProxiesCommand::class,
            'orm:convert-mapping' => ConvertMappingCommand::class,
            'orm:run-dql' => RunDqlCommand::class,
            'orm:validate-schema' => ValidateSchemaCommand::class,
            'orm:info' => InfoCommand::class,
            'orm:mapping:describe' => MappingDescribeCommand::class,

            //Migrations (already has names in $defaultName)
            0 => DumpSchemaCommand::class,
            1 => ExecuteCommand::class,
            2 => GenerateCommand::class,
            3 => LatestCommand::class,
            4 => MigrateCommand::class,
            5 => RollupCommand::class,
            6 => StatusCommand::class,
        ];
    }
}
