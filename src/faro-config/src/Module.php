<?php

namespace Sicet7\Faro\Config;

use DI\ContainerBuilder;
use Sicet7\Faro\Config\Interfaces\HasConfigInterface;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Core\ModuleList;

class Module extends AbstractModule implements BeforeBuildInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'faro-config';
    }

    /**
     * @param ModuleList $moduleList
     * @param ContainerBuilder $containerBuilder
     * @return void
     * @throws Exceptions\ConfigException
     */
    public static function beforeBuild(ModuleList $moduleList, ContainerBuilder $containerBuilder): void
    {
        $config = [];
        $processed = [];
        foreach ($moduleList->getLoadedModules() as $loadedModule) {
            self::loadConfig($loadedModule, $processed, $config, $moduleList);
        }
        unset($processed);
        $containerBuilder->addDefinitions([
            Config::class => new Config($config),
        ]);
    }

    /**
     * @param string $module
     * @param array $processed
     * @param array $config
     * @param ModuleList $moduleList
     * @return void
     */
    private static function loadConfig(
        string $module,
        array &$processed,
        array &$config,
        ModuleList $moduleList
    ): void {
        if (in_array($module, $processed)) {
            return;
        }
        $modules = $moduleList->getLoadedModules();
        /** @var AbstractModule $module */
        $dependencies = $module::getDependencies();
        if (!empty($dependencies)) {
            foreach ($dependencies as $dependency) {
                self::loadConfig($modules[$dependency], $processed, $config, $moduleList);
            }
        }
        if (is_subclass_of($module, HasConfigInterface::class)) {
            foreach ($module::getConfigPaths() as $configPath) {
                $configData = Config::readFromPath($configPath);
                if (is_array($configData)) {
                    $tmp = $config;
                    $tmp = array_merge($tmp, $configData);
                    $config = $tmp;
                }
            }
        }
        $processed[] = $module;
    }
}
