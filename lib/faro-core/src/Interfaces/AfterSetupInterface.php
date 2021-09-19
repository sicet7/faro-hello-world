<?php

namespace Sicet7\Faro\Core\Interfaces;

use Psr\Container\ContainerInterface;

interface AfterSetupInterface
{
    /**
     * @param ContainerInterface $container
     */
    public static function afterSetup(ContainerInterface $container): void;
}
