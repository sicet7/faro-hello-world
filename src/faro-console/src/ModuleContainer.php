<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

use DI\Container;
use DI\ContainerBuilder;
use DI\Invoker\FactoryParameterResolver;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Sicet7\Faro\Core\Event\Dispatcher;
use Sicet7\Faro\Core\Event\ListenerContainer;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Console\Event\SymfonyDispatcher;
use Sicet7\Faro\Core\Exception\ModuleException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

use function DI\create;
use function DI\get;

class ModuleContainer
{
    private static ?ModuleContainer $instance = null;

    /**
     * @return ModuleContainer
     */
    public static function getInstance(): ModuleContainer
    {
        if (!(static::$instance instanceof ModuleContainer)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param string $moduleFqn
     * @throws ModuleException
     */
    public static function registerModule(string $moduleFqn): void
    {
        static::getInstance()->addModule($moduleFqn);
    }

    /**
     * Should be a class which the ContainerBuilder
     *
     * @var string
     */
    protected string $containerClass = Container::class;

    /**
     * @var ContainerBuilder
     */
    private ContainerBuilder $containerBuilder;

    /**
     * @var CommandFactoryMapper
     */
    private CommandFactoryMapper $commandFactoryMapper;

    /**
     * @var ModuleLoader[]
     */
    private array $moduleList = [];

    /**
     * ModuleContainer constructor.
     */
    public function __construct()
    {
        $this->commandFactoryMapper = new CommandFactoryMapper();
        $this->containerBuilder = new ContainerBuilder($this->containerClass);
        $this->containerBuilder->useAutowiring(false);
        $this->containerBuilder->useAnnotations(false);
        $this->containerBuilder->addDefinitions([
            CommandFactory::class => create(CommandFactory::class)
                ->constructor(create(ResolverChain::class)
                    ->constructor([
                        create(AssociativeArrayResolver::class),
                        create(FactoryParameterResolver::class)
                            ->constructor(get(ContainerInterface::class)),
                        create(DefaultValueResolver::class)
                    ])),
            ListenerContainer::class => create(ListenerContainer::class)
                ->constructor(get(ContainerInterface::class)),
            ListenerContainerInterface::class => get(ListenerContainer::class),
            ListenerProviderInterface::class => get(ListenerContainerInterface::class),
            Dispatcher::class => create(Dispatcher::class)
                ->constructor(get(ListenerProviderInterface::class)),
            PsrEventDispatcherInterface::class => get(Dispatcher::class),
            SymfonyEventDispatcherInterface::class => create(SymfonyDispatcher::class)
                ->constructor(get(PsrEventDispatcherInterface::class)),
            Application::class => function (
                CommandLoaderInterface $commandLoader,
                SymfonyEventDispatcherInterface $eventDispatcher
            ) {
                $app = new Application();
                $app->setCommandLoader($commandLoader);
                $app->setDispatcher($eventDispatcher);
                return $app;
            },
        ]);
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        return $this->containerBuilder;
    }

    /**
     * @return CommandFactoryMapper
     */
    public function getCommandFactoryMapper(): CommandFactoryMapper
    {
        return $this->commandFactoryMapper;
    }

    /**
     * @param ContainerInterface $container
     * @throws ModuleException
     */
    protected function setupModules(ContainerInterface $container)
    {
        do {
            $setupCount = 0;
            foreach ($this->getList() as $loader) {
                if (!$loader->isEnabled() || $loader->isSetup()) {
                    continue;
                }
                if (!$loader->isSetup()) {
                    $loader->setup($container);
                    $setupCount++;
                }
            }
        } while ($setupCount !== 0);

        foreach ($this->getList() as $loader) {
            if ($loader->isEnabled() && !$loader->isSetup()) {
                throw new ModuleException("Module setup failed for module: {$loader->getModuleFqn()}");
            }
        }
    }

    /**
     * @param string $moduleFqn
     * @throws ModuleException
     */
    public function addModule(string $moduleFqn): void
    {
        $moduleLoader = $this->createLoader($moduleFqn);
        if ($moduleLoader->isEnabled()) {
            $this->moduleList[$moduleLoader->getName()] = $moduleLoader;
        }
    }

    /**
     * Creates the loader for the modules.
     *
     * @param string $moduleFqn
     * @return ModuleLoader
     * @throws ModuleException
     */
    protected function createLoader(string $moduleFqn): ModuleLoader
    {
        return new ModuleLoader($moduleFqn);
    }

    /**
     * @return ModuleLoader[]
     */
    public function getList(): array
    {
        return $this->moduleList;
    }

    /**
     * @return ContainerInterface
     * @throws ModuleException
     */
    public function buildContainer(): ContainerInterface
    {
        try {
            $this->loadDefinitions();
            $container = $this->getContainerBuilder()->build();
            $this->setupModules($container);
            return $container;
        } catch (\Exception $exception) {
            if ($exception instanceof ModuleException) {
                throw $exception;
            }
            throw new ModuleException($exception, $exception->getCode(), $exception);
        }
    }

    /**
     * @throws ModuleException
     */
    protected function loadDefinitions()
    {
        do {
            $actionCount = 0;
            foreach ($this->getList() as $loader) {
                if (!$loader->isEnabled() || $loader->isLoaded()) {
                    continue;
                }

                if (!$loader->isDependenciesResolved()) {
                    $loader->resolveDependencies($this->getList());
                    $actionCount++;
                }

                if (!$loader->isLoaded() && $loader->isDependenciesLoaded()) {
                    $loader->load($this->getContainerBuilder(), $this->getCommandFactoryMapper());
                    $actionCount++;
                }
            }
        } while ($actionCount !== 0);

        foreach ($this->getList() as $loader) {
            if ($loader->isEnabled() && !$loader->isLoaded()) {
                throw new ModuleException("Failed to load module: {$loader->getModuleFqn()}");
            }
        }

        $this->getContainerBuilder()->addDefinitions([
            CommandLoaderInterface::class => create(ContainerCommandLoader::class)
                ->constructor(get(ContainerInterface::class), $this->getCommandFactoryMapper()->getMap()),
        ]);
    }
}
