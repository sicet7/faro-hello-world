<?php

namespace Sicet7\Faro\Slim;

use DI\FactoryInterface;
use JetBrains\PhpStorm\ArrayShape;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Core\Attributes\Definition;
use Sicet7\Faro\Core\BaseModule;
use Invoker\CallableResolver as PHPDICallableResolver;
use Sicet7\Faro\Core\ContainerBuilderProxy;
use Sicet7\Faro\Core\Factories\DefaultFactory;
use Sicet7\Faro\Core\Interfaces\BeforeBuildInterface;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Core\Tools\ClassReflection;
use Sicet7\Faro\Event\Interfaces\HasListenersInterface;
use Sicet7\Faro\Slim\Attributes\Routing\Any;
use Sicet7\Faro\Slim\Attributes\Routing\Delete;
use Sicet7\Faro\Slim\Attributes\Routing\Get;
use Sicet7\Faro\Slim\Attributes\Routing\Middleware;
use Sicet7\Faro\Slim\Attributes\Routing\Options;
use Sicet7\Faro\Slim\Attributes\Routing\Patch;
use Sicet7\Faro\Slim\Attributes\Routing\Post;
use Sicet7\Faro\Slim\Attributes\Routing\Put;
use Sicet7\Faro\Slim\Attributes\Routing\Route;
use Sicet7\Faro\Slim\Factories\ControllerInvocationStrategyFactory;
use Sicet7\Faro\Slim\Factories\ApplicationFactory;
use Sicet7\Faro\Slim\Interfaces\ApplicationFactoryInterface;
use Sicet7\Faro\Slim\Interfaces\ControllerInvocationStrategyFactoryInterface;
use Sicet7\Faro\Slim\Interfaces\HasRoutesInterface;
use Sicet7\Faro\Slim\Interfaces\RouteLoaderInterface;
use Sicet7\Faro\Slim\Listeners\RequestListener;
use Slim\App;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Routing\RouteCollector;

use function DI\create;
use function DI\factory;
use function DI\get;

class Module extends BaseModule implements HasListenersInterface, BeforeBuildInterface
{
    private const ROUTE_ATTRIBUTES = [
        Any::class,
        Delete::class,
        Get::class,
        Options::class,
        Patch::class,
        Post::class,
        Put::class,
        Route::class,
    ];

    private const MIDDLEWARE_ATTRIBUTE = Middleware::class;

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Event\Module::class,
        ];
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            App::class => factory([ApplicationFactoryInterface::class, 'create']),
            ApplicationFactoryInterface::class => create(ApplicationFactory::class)
                ->constructor(get(ContainerInterface::class)),
            Psr17Factory::class => create(Psr17Factory::class),
            RequestFactoryInterface::class => get(Psr17Factory::class),
            ResponseFactoryInterface::class => get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => get(Psr17Factory::class),
            StreamFactoryInterface::class => get(Psr17Factory::class),
            UploadedFileFactoryInterface::class => get(Psr17Factory::class),
            UriFactoryInterface::class => get(Psr17Factory::class),
            RouteCollector::class  => create(RouteCollector::class)
                ->constructor(
                    get(ResponseFactoryInterface::class),
                    get(CallableResolverInterface::class),
                    get(ContainerInterface::class),
                    get(InvocationStrategyInterface::class)
                ),
            RouteCollectorInterface::class => get(RouteCollector::class),
            PHPDICallableResolver::class => create(PHPDICallableResolver::class)
                ->constructor(get(ContainerInterface::class)),
            ControllerInvocationStrategyFactoryInterface::class =>
                create(ControllerInvocationStrategyFactory::class)
                    ->constructor(get(ContainerInterface::class)),
            InvocationStrategyInterface::class =>
                factory([ControllerInvocationStrategyFactoryInterface::class, 'create']),
            CallableResolverInterface::class => create(CallableResolver::class)
                ->constructor(get(PHPDICallableResolver::class)),
        ];
    }

    /**
     * @return string[]
     */
    public static function getListeners(): array
    {
        return [
            RequestListener::class
        ];
    }

    /**
     * @param ContainerInterface $container
     * @param RouteCollectorInterface $routeCollector
     * @param LoggerInterface $logger
     * @param FactoryInterface $factory
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public static function setup(
        ContainerInterface $container,
        RouteCollectorInterface $routeCollector,
        LoggerInterface $logger,
        FactoryInterface $factory
    ): void {
        /** @var string[] $foundRoutes */
        $foundRoutes = $container->get('routes.found');
        //TODO: finish implementing this.
        $routeGroups = [];
        foreach ($foundRoutes as $foundRoute) {
            if (!class_exists($foundRoute) || !method_exists($foundRoute, '__invoke')) {
                $logger->warning('Failed to resolve route "' . $foundRoute . '".');
                continue;
            }
            $routeReflection = new \ReflectionClass($foundRoute);
            $routeAttributes = ClassReflection::getAttributes($routeReflection, self::ROUTE_ATTRIBUTES);
            $middlewareAttributes = ClassReflection::getAttributes($routeReflection, self::MIDDLEWARE_ATTRIBUTE);
            foreach ($routeAttributes as $routeAttribute) {
                $routeAttributeInstance = $routeAttribute->newInstance();
                if (!($routeAttributeInstance instanceof Route)) {
                    continue;
                }
                $routeGroups[$routeAttributeInstance->getGroupFqcn() ?? 0][] = [
                    'route' => $routeAttributeInstance,
                    'middleware' => $middlewareAttributes,
                ];
            }
        }
    }

    /**
     * @param ContainerBuilderProxy $builderProxy
     * @return void
     */
    public static function beforeBuild(ContainerBuilderProxy $builderProxy): void
    {
        $foundRoutes = [];
        $builderProxy->runOnLoadedDependencyOrder(function (string $moduleFqcn) use ($builderProxy, &$foundRoutes) {
            $class = new \ReflectionClass($moduleFqcn);
            if (ClassReflection::readStaticProperty($class, 'enableAttributeLoading') !== true) {
                return;
            }
            $moduleDirectory = ClassReflection::getDirectory($class);
            $moduleNamespace = ClassReflection::getNamespace($class);
            if ($moduleDirectory === null || $moduleNamespace === null) {
                return;
            }
            $moduleClasses = PSR4::getFQCNs($moduleNamespace, $moduleDirectory);

            foreach ($moduleClasses as $moduleClass) {
                $moduleClassReflection = new \ReflectionClass($moduleClass);
                if (!ClassReflection::hasAttribute($moduleClassReflection, self::ROUTE_ATTRIBUTES)) {
                    continue;
                }
                if (
                    !ClassReflection::hasAttribute($moduleClassReflection, Definition::class) &&
                    !$builderProxy->getModuleList()->isObjectDefined($moduleClass)
                ) {
                    $builderProxy->addDefinition($moduleClass, factory([DefaultFactory::class, 'create']));
                }
                $foundRoutes[] = $moduleClass;
            }
        });
        $builderProxy->addDefinition('routes.found', array_unique($foundRoutes));
    }

    /**
     * @param Route $routeAttribute
     * @param RouteCollectorInterface $routeCollector
     * @param array $middlewareAttributes
     * @return void
     * @internal
     */
    public static function registerRoute(
        Route $routeAttribute,
        RouteCollectorInterface $routeCollector,
        array $middlewareAttributes = []
    ): void {

    }
}
