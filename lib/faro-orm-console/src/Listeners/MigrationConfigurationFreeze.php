<?php

namespace Sicet7\Faro\ORM\Console\Listeners;

use Doctrine\Migrations\Configuration\Configuration as MigrationConfiguration;
use Sicet7\Faro\Core\BuildLock;
use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;

#[ListensTo(BuildLock::class)]
class MigrationConfigurationFreeze implements ListenerInterface
{
    /**
     * @var MigrationConfiguration
     */
    private MigrationConfiguration $configuration;

    /**
     * @param MigrationConfiguration $configuration
     */
    public function __construct(MigrationConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param object $event
     * @return void
     */
    public function execute(object $event): void
    {
        $this->configuration->freeze();
    }
}
