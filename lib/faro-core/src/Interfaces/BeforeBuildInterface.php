<?php

namespace Sicet7\Faro\Core\Interfaces;

use DI\ContainerBuilder;
use Sicet7\Faro\Core\ModuleList;

interface BeforeBuildInterface
{
    /**
     * @param ModuleList $moduleList
     * @param ContainerBuilder $containerBuilder
     */
    public static function beforeBuild(ModuleList $moduleList, ContainerBuilder $containerBuilder): void;
}
