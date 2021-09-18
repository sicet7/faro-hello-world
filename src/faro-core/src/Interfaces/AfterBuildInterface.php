<?php

namespace Sicet7\Faro\Core\Interfaces;

use Psr\Container\ContainerInterface;

interface AfterBuildInterface
{
    /**
     * @param ContainerInterface $container
     */
    public static function afterBuild(ContainerInterface $container): void;
}
