<?php

namespace Sicet7\Faro\Core;

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
}
