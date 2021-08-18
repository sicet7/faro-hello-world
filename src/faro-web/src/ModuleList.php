<?php

namespace Sicet7\Faro\Web;

class ModuleList
{
    private array $loadedModules;

    /**
     * ModuleList constructor.
     * @param array $loadedModules
     */
    public function __construct(array $loadedModules)
    {
        $this->loadedModules = $loadedModules;
    }

    /**
     * @return array
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
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
}
