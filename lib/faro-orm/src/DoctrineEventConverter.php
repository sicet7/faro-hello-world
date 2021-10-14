<?php

namespace Sicet7\Faro\ORM;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Events as DBALEvents;
use Doctrine\ORM\Events as ORMEvents;
use Doctrine\Migrations\Events as MigrationEvents;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sicet7\Faro\Event\Interfaces\ListenerProviderInterface;
use Sicet7\Faro\ORM\Events\DBAL\PostConnectEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaAlterTableAddColumnEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaAlterTableChangeColumnEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaAlterTableEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaAlterTableRemoveColumnEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaAlterTableRenameColumnEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaColumnDefinitionEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaCreateTableColumnEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaCreateTableEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaDropTableEvent;
use Sicet7\Faro\ORM\Events\DBAL\SchemaIndexDefinitionEvent;
use Sicet7\Faro\ORM\Events\DoctrineEvent;
use Sicet7\Faro\ORM\Events\Migration\MigratedEvent;
use Sicet7\Faro\ORM\Events\Migration\MigratingEvent;
use Sicet7\Faro\ORM\Events\Migration\VersionExecutedEvent;
use Sicet7\Faro\ORM\Events\Migration\VersionExecutingEvent;
use Sicet7\Faro\ORM\Events\Migration\VersionSkippedEvent;
use Sicet7\Faro\ORM\Events\ORM\ClassMetadataNotFoundEvent;
use Sicet7\Faro\ORM\Events\ORM\ClearEvent;
use Sicet7\Faro\ORM\Events\ORM\FlushEvent;
use Sicet7\Faro\ORM\Events\ORM\LoadClassMetadataEvent;
use Sicet7\Faro\ORM\Events\ORM\PostFlushEvent;
use Sicet7\Faro\ORM\Events\ORM\PostLoadEvent;
use Sicet7\Faro\ORM\Events\ORM\PostPersistEvent;
use Sicet7\Faro\ORM\Events\ORM\PostRemoveEvent;
use Sicet7\Faro\ORM\Events\ORM\PostUpdateEvent;
use Sicet7\Faro\ORM\Events\ORM\PreFlushEvent;
use Sicet7\Faro\ORM\Events\ORM\PrePersistEvent;
use Sicet7\Faro\ORM\Events\ORM\PreRemoveEvent;
use Sicet7\Faro\ORM\Events\ORM\PreUpdateEvent;

//TODO: find a way to implement the "getListeners" method for better compatibility
class DoctrineEventConverter extends EventManager
{
    /**
     * @var array
     */
    private const EVENT_MAPPINGS = [
        DBALEvents::postConnect => PostConnectEvent::class,
        DBALEvents::onSchemaCreateTable => SchemaCreateTableEvent::class,
        DBALEvents::onSchemaCreateTableColumn => SchemaCreateTableColumnEvent::class,
        DBALEvents::onSchemaDropTable => SchemaDropTableEvent::class,
        DBALEvents::onSchemaAlterTable => SchemaAlterTableEvent::class,
        DBALEvents::onSchemaAlterTableAddColumn => SchemaAlterTableAddColumnEvent::class,
        DBALEvents::onSchemaAlterTableRemoveColumn => SchemaAlterTableRemoveColumnEvent::class,
        DBALEvents::onSchemaAlterTableChangeColumn => SchemaAlterTableChangeColumnEvent::class,
        DBALEvents::onSchemaAlterTableRenameColumn => SchemaAlterTableRenameColumnEvent::class,
        DBALEvents::onSchemaColumnDefinition => SchemaColumnDefinitionEvent::class,
        DBALEvents::onSchemaIndexDefinition => SchemaIndexDefinitionEvent::class,
        ORMEvents::preRemove => PreRemoveEvent::class,
        ORMEvents::postRemove => PostRemoveEvent::class,
        ORMEvents::prePersist => PrePersistEvent::class,
        ORMEvents::postPersist => PostPersistEvent::class,
        ORMEvents::preUpdate => PreUpdateEvent::class,
        ORMEvents::postUpdate => PostUpdateEvent::class,
        ORMEvents::postLoad => PostLoadEvent::class,
        ORMEvents::loadClassMetadata => LoadClassMetadataEvent::class,
        ORMEvents::onClassMetadataNotFound => ClassMetadataNotFoundEvent::class,
        ORMEvents::preFlush => PreFlushEvent::class,
        ORMEvents::onFlush => FlushEvent::class,
        ORMEvents::postFlush => PostFlushEvent::class,
        ORMEvents::onClear => ClearEvent::class,
        MigrationEvents::onMigrationsMigrating => MigratingEvent::class,
        MigrationEvents::onMigrationsMigrated => MigratedEvent::class,
        MigrationEvents::onMigrationsVersionExecuting => VersionExecutingEvent::class,
        MigrationEvents::onMigrationsVersionExecuted => VersionExecutedEvent::class,
        MigrationEvents::onMigrationsVersionSkipped => VersionSkippedEvent::class,
    ];

    /**
     * @var ListenerProviderInterface
     */
    private ListenerProviderInterface $listenerProvider;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * DoctrineEventConverter constructor.
     * @param ListenerProviderInterface $listenerProvider
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ListenerProviderInterface $listenerProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->listenerProvider = $listenerProvider;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $eventName
     * @param EventArgs|null $eventArgs
     * @return void
     */
    public function dispatchEvent($eventName, ?EventArgs $eventArgs = null)
    {
        parent::dispatchEvent($eventName, $eventArgs);
        $eventObject = $this->convertEventToObject($eventName, $eventArgs);
        if ($eventObject !== null) {
            $this->dispatcher->dispatch($eventObject);
        }
    }

    /**
     * @param string $event
     * @return bool
     */
    public function hasListeners($event)
    {
        $eventObject = $this->convertEventToObject($event);
        if ($eventObject !== null && $this->listenerProvider->hasListenersForEvent($eventObject)) {
            return true;
        }
        return parent::hasListeners($event);
    }

    /**
     * @param string $event
     * @param EventArgs|null $eventArgs
     * @return DoctrineEvent|null
     */
    private function convertEventToObject($event, ?EventArgs $eventArgs = null): ?DoctrineEvent
    {
        if (!array_key_exists($event, self::EVENT_MAPPINGS)) {
            return null;
        }
        $eventClass = self::EVENT_MAPPINGS[$event];
        return new $eventClass($eventArgs);
    }
}
