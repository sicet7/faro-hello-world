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
     * ModuleList constructor.
     * @param array $loadedModules
     * @param array $registeredModules
     */
    public function __construct(array $loadedModules, array $registeredModules)
    {
        $this->loadedModules = $loadedModules;
        $this->registeredModules = $registeredModules;
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
     * @param string $fqn
     * @return bool
     */
    public function isModuleClassLoaded(string $fqn): bool
    {
        return in_array($fqn, $this->getLoadedModules());
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
     * @param string $fqn
     * @return bool
     */
    public function isModuleClassRegistered(string $fqn): bool
    {
        return in_array($fqn, $this->getRegisteredModules());
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
     * @param callable $callable
     * @return void
     * @throws ModuleException
     */
    public function runOnLoadedDependencyOrder(callable $callable): void
    {
        $moduleList = $this->getLoadedModules();
        $alreadyRan = [];
        foreach ($moduleList as $moduleFqcn) {
            $this->runCallableOnDependencyOrder(
                $moduleList,
                $moduleFqcn,
                $callable,
                $alreadyRan
            );
        }
    }

    /**
     * @param array $moduleList
     * @param string $moduleFqcn
     * @param callable $callable
     * @param array $alreadyRan
     * @param string|null $initialFqcn
     * @return void
     * @throws ModuleException
     */
    private function runCallableOnDependencyOrder(
        array $moduleList,
        string $moduleFqcn,
        callable $callable,
        array &$alreadyRan,
        ?string $initialFqcn = null
    ): void {
        /** @var AbstractModule $moduleFqcn */
        $name = $moduleFqcn::getName();
        if (in_array($moduleFqcn, $alreadyRan)) {
            return;
        }
        if ($initialFqcn !== null && $moduleFqcn == $initialFqcn) {
            throw new ModuleException('Dependency loop detected for module: "' . $name . '".');
        }
        if ($initialFqcn === null) {
            $initialFqcn = $moduleFqcn;
        }
        foreach ($moduleFqcn::getDependencies() as $dependency) {
            if (!array_key_exists($dependency, $moduleList)) {
                throw new ModuleException(
                    'Missing dependency: "' . $dependency . '" for module: "' . $name . '".'
                );
            }
            $dependencyFqcn = $moduleList[$dependency];
            /** @var AbstractModule $dependencyFqcn */
            $this->runCallableOnDependencyOrder(
                $moduleList,
                $dependencyFqcn,
                $callable,
                $alreadyRan,
                $initialFqcn
            );
        }
        $callable($moduleFqcn);
        $alreadyRan[$name] = $moduleFqcn;
    }
}
