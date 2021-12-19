<?php

namespace Sicet7\Faro\Core;

use Sicet7\Faro\Core\Exception\ModuleException;

class ModuleList
{
    /**
     * @var array
     */
    private array $loadedModules;

    /**
     * @var array
     */
    private array $registeredModules;

    /**
     * @var array
     */
    private array $definedObjects;

    /**
     * ModuleList constructor.
     * @param array $loadedModules
     * @param array $registeredModules
     * @param array $definedObjects
     */
    public function __construct(
        array $loadedModules,
        array $registeredModules,
        array $definedObjects
    ) {
        $this->loadedModules = $loadedModules;
        $this->registeredModules = $registeredModules;
        $this->definedObjects = $definedObjects;
    }

    /**
     * @return array
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * @return array
     */
    public function getRegisteredModules(): array
    {
        return $this->registeredModules;
    }

    /**
     * @return array
     */
    public function getDefinedObjects(): array
    {
        return $this->definedObjects;
    }

    /**
     * @param string $fqcn
     * @return bool
     */
    public function isModuleClassLoaded(string $fqcn): bool
    {
        return in_array($fqcn, $this->getLoadedModules());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isModuleLoaded(string $name): bool
    {
        return array_key_exists($name, $this->getLoadedModules());
    }

    /**
     * @param string $fqcn
     * @return bool
     */
    public function isModuleClassRegistered(string $fqcn): bool
    {
        return in_array($fqcn, $this->getRegisteredModules());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isModuleRegistered(string $name): bool
    {
        return array_key_exists($name, $this->getRegisteredModules());
    }

    /**
     * @param string $fqcn
     * @return bool
     */
    public function isObjectDefined(string $fqcn): bool
    {
        return array_key_exists($fqcn, $this->definedObjects);
    }

    /**
     * @param string $fqcn
     * @return string|null
     */
    public function findDefiningModule(string $fqcn): ?string
    {
        return $this->definedObjects[$fqcn] ?? null;
    }

    /**
     * @param callable $callable
     * @return void
     * @throws ModuleException
     */
    public function runOnLoadedDependencyOrder(callable $callable): void
    {
        $moduleList = $this->getLoadedModules();
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
