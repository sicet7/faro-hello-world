<?php

namespace Sicet7\Faro\Core\Interfaces;

use DI\ContainerBuilder;
use Sicet7\Faro\Core\ContainerBuilderProxy;
use Sicet7\Faro\Core\ModuleList;

interface BeforeBuildInterface
{
    /**
     * @param ContainerBuilderProxy $builderProxy
     * @return void
     */
    public static function beforeBuild(ContainerBuilderProxy $builderProxy): void;
}
