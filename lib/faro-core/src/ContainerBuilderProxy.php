<?php

namespace Sicet7\Faro\Core;

use DI\ContainerBuilder;
use Sicet7\Faro\Core\Exception\ModuleException;

class ContainerBuilderProxy
{
    /**
     * @var ContainerBuilder
     */
    private ContainerBuilder $containerBuilder;

    /**
     * @var ModuleList
     */
    private ModuleList $moduleList;

    /**
     * @var string
     */
    private string $moduleFqcn;

    /**
     * @param ContainerBuilder $containerBuilder
     * @param ModuleList $moduleList
     * @param string $moduleFqcn
     */
    public function __construct(
        ContainerBuilder $containerBuilder,
        ModuleList $moduleList,
        string $moduleFqcn
    ) {
        $this->containerBuilder = $containerBuilder;
        $this->moduleList = $moduleList;
        $this->moduleFqcn = $moduleFqcn;
    }

    /**
     * @param string $fqcn
     * @param mixed $factory
     * @return void
     */
    public function addDefinition(string $fqcn, mixed $factory): void
    {
        $definedObjects = $this->moduleList->getDefinedObjects();
        $definedObjects[$fqcn] = $this->moduleFqcn;
        $this->moduleList = new ModuleList(
            $this->moduleList->getLoadedModules(),
            $this->moduleList->getRegisteredModules(),
            $definedObjects
        );
        $this->containerBuilder->addDefinitions([
            $fqcn => $factory,
        ]);
    }

    /**
     * @return ModuleList
     */
    public function getModuleList(): ModuleList
    {
        return $this->moduleList;
    }

    /**
     * @param callable $callable
     * @return void
     * @throws ModuleException
     */
    public function runOnLoadedDependencyOrder(callable $callable): void
    {
        $moduleList = $this->getModuleList()->getLoadedModules();
        $alreadyRan = [];
        foreach ($moduleList as $moduleFqcn) {
            ModuleContainer::runCallableOnDependencyOrder(
                $moduleList,
                $moduleFqcn,
                $callable,
                $alreadyRan
            );
        }
    }
}
