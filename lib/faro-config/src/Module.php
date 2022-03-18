<?php

namespace Sicet7\Faro\Config;

use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\ContainerBuilderProxy;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;

class Module extends BaseModule implements BeforeBuildInterface
{
    /**
     * @param ContainerBuilderProxy $builderProxy
     * @return void
     * @throws Exceptions\ConfigException
     */
    public static function beforeBuild(ContainerBuilderProxy $builderProxy): void
    {
        $config = [];
        $builderProxy->runOnLoadedDependencyOrder(function (string $moduleFqcn) use (&$config) {
            if (is_subclass_of($moduleFqcn, HasConfigInterface::class)) {
                foreach ($moduleFqcn::getConfigPaths() as $configPath) {
                    $configData = Config::readFromPath($configPath);
                    if (is_array($configData)) {
                        $config = array_merge($config, $configData);
                    }
                }
            }
        });
        $builderProxy->addDefinition(Config::class, new Config($config));
    }
}
