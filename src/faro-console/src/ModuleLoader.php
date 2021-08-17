<?php

namespace Sicet7\Faro\Console;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Symfony\Component\Console\Command\Command;

class ModuleLoader
{
    /**
     * @var array
     */
    private array $commandDefinitions = [];

    /**
     * @var string
     */
    private string $moduleFqn;

    /**
     * @var bool
     */
    private bool $loaded = false;

    /**
     * @var bool
     */
    private bool $setup = false;

    /**
     * @var bool
     */
    private bool $enabled = false;

    /**
     * @var string[]
     */
    private array $dependencyNames = [];

    /**
     * @var ModuleLoader[]
     */
    private array $dependencyLoaders = [];

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array
     */
    private array $definitions = [];

    /**
     * ModuleLoader constructor.
     * @param string $moduleFqn
     * @throws ModuleException
     */
    public function __construct(string $moduleFqn)
    {
        $this->moduleFqn = '\\' . ltrim($moduleFqn, '\\');
        $this->init();
    }

    /**
     * @throws ModuleException
     */
    private function init(): void
    {
        if (!is_subclass_of($this->getModuleFqn(), AbstractModule::class)) {
            throw new ModuleException(
                "Invalid module class. \"{$this->getModuleFqn()}\" must be an instance of " .
                '"' . AbstractModule::class . '".'
            );
        }

        $enabled = $this->moduleRead('isEnabled');
        if (!is_bool($enabled)) {
            throw new ModuleException(
                "Invalid \"isEnabled\" state on module: \"{$this->getModuleFqn()}\""
            );
        }
        $this->enabled = $enabled;

        $dependencies = $this->moduleRead('getDependencies');
        if (!is_array($dependencies)) {
            throw new ModuleException(
                "Unknown dependency type on module: \"{$this->getModuleFqn()}\""
            );
        }
        $this->dependencyNames = $dependencies;

        $name = $this->moduleRead('getName');
        if (!is_string($name) || empty($name)) {
            throw new ModuleException(
                "The module name of {$this->getModuleFqn()} must be a non empty string value."
            );
        }
        $this->name = $name;

        $definitions = $this->moduleRead('getDefinitions');
        if (!is_array($definitions)) {
            throw new ModuleException(
                "Invalid definitions type on module: \"{$this->getModuleFqn()}\"."
            );
        }
        $this->definitions = $definitions;

        $commandDefinitions = $this->moduleRead('getCommandDefinitions');
        if (!is_array($commandDefinitions)) {
            throw new ModuleException(
                "Invalid command definitions type on module: \"{$this->getModuleFqn()}\"."
            );
        }
        foreach ($commandDefinitions as $commandDefinition) {
            if (!is_subclass_of($commandDefinition, Command::class)) {
                throw new ModuleException(
                    'Invalid command definition. ' .
                    var_export($commandDefinition, true) .
                    ' is not a valid command'
                );
            }
        }
        $this->commandDefinitions = $commandDefinitions;
    }

    /**
     * @param string $method
     * @return mixed
     */
    private function moduleRead(string $method): mixed
    {
        return call_user_func([$this->getModuleFqn(), $method]);
    }

    /**
     * @return string
     */
    public function getModuleFqn(): string
    {
        return $this->moduleFqn;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @return bool
     */
    public function isSetup(): bool
    {
        return $this->setup;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return array
     */
    public function getCommandDefinitions(): array
    {
        return $this->commandDefinitions;
    }

    /**
     * @return string[]
     */
    public function getDependencyNames(): array
    {
        return $this->dependencyNames;
    }

    /**
     * @return bool
     */
    public function isDependenciesResolved(): bool
    {
        foreach ($this->getDependencyNames() as $dependencyName) {
            if (!array_key_exists($dependencyName, $this->dependencyLoaders)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isDependenciesLoaded(): bool
    {
        if (!$this->isDependenciesResolved()) {
            return false;
        }
        foreach ($this->dependencyLoaders as $moduleLoader) {
            if (!$moduleLoader->isLoaded()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param ModuleLoader[] $moduleLoaders
     * @throws ModuleException
     */
    public function resolveDependencies(array $moduleLoaders): void
    {
        $this->dependencyLoaders = [];
        foreach ($moduleLoaders as $moduleLoader) {
            if (in_array($moduleLoader->getName(), $this->getDependencyNames())) {
                $this->dependencyLoaders[$moduleLoader->getName()] = $moduleLoader;
            }
        }
        if (!$this->isDependenciesResolved()) {
            throw new ModuleException(
                "Failed to resolve dependency for module: {$this->getModuleFqn()}"
            );
        }
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @param CommandFactoryMapper $commandFactoryMapper
     * @throws ModuleException
     */
    public function load(ContainerBuilder $containerBuilder, CommandFactoryMapper $commandFactoryMapper): void
    {
        if (!empty($this->getDefinitions())) {
            $containerBuilder->addDefinitions($this->getDefinitions());
        }
        foreach ($this->getCommandDefinitions() as $commandName => $commandDefinition) {
            if (!is_string($commandName)) {
                throw new ModuleException(
                    'Failed to determine command name for command: ' . $commandDefinition
                );
            }
            if (array_key_exists($commandName, $commandFactoryMapper->getMap())) {
                throw new ModuleException(
                    "Command name collision. Command \"$commandName\" already exists."
                );
            }
            $containerBuilder->addDefinitions([
                $commandDefinition => $commandFactoryMapper->mapCommand($commandName, $commandDefinition),
            ]);
        }
        $this->loaded = true;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setup(ContainerInterface $container): void
    {
        call_user_func([$this->getModuleFqn(), 'setup'], $container);
        $this->setup = true;
    }
}
