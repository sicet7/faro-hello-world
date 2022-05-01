<?php

namespace Server\App\Web;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Server\App\Core\Module as Core;
use Server\App\Core\Services\EnvironmentService;
use Server\App\Web\Http\Middlewares\BodyParsingMiddleware as ResolvingBodyParsingMiddleware;
use Server\App\Web\Providers\BodyParserProvider;
use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Log\Module as LogModule;
use Sicet7\Faro\Slim\Interfaces\HasRoutesInterface;
use Sicet7\Faro\Slim\Module as SlimModule;
use Slim\App;
use Slim\Middleware\BodyParsingMiddleware as SlimBodyParsingMiddlewareAlias;

use function DI\create;
use function DI\get;

class Module extends BaseModule implements HasRoutesInterface
{
    /**
     * @var bool
     */
    protected static bool $enableAttributeDefinitions = true;

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            /*BodyParserProvider::class => create(BodyParserProvider::class)
                ->constructor(get(ContainerInterface::class)),
            ResolvingBodyParsingMiddleware::class => function (
                BodyParserProvider $bodyParserProvider,
                ContainerInterface $container
            ): ResolvingBodyParsingMiddleware {
                $middleware = new ResolvingBodyParsingMiddleware($bodyParserProvider);
                if ($container->has('web.middleware.bodyparsers')) {
                    $bodyParsers = $container->get('web.middleware.bodyparsers');
                    if (is_array($bodyParsers)) {
                        foreach ($bodyParsers as $mimeType => $bodyParser) {
                            $middleware->registerResolvableBodyParser($mimeType, $bodyParser);
                        }
                    }
                }
                return $middleware;
            },
            SlimBodyParsingMiddlewareAlias::class => get(ResolvingBodyParsingMiddleware::class),*/
        ];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            SlimModule::class,
            LogModule::class,
            Core::class,
        ];
    }

    /**
     * @return array
     */
    public static function getRoutes(): array
    {
        return PSR4::getFQCNs(__NAMESPACE__ . '\\Http\\Routes', __DIR__ . '/Http/Routes');
    }

    /**
     * @param ContainerInterface $container
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function setup(ContainerInterface $container): void
    {
        /** @var App $app */
        /** @var EnvironmentService $environment */
        $app = $container->get(App::class);
        $environment = $container->get(EnvironmentService::class);
//        $app->add($container->get(SlimBodyParsingMiddlewareAlias::class));
        $app->addRoutingMiddleware();
        $logger = (!$container->has(LoggerInterface::class) ? null : $container->get(LoggerInterface::class));
        $app->addErrorMiddleware(
            $environment->isDevelopment(),
            true,
            ($environment->isDevelopment() || $environment->isStaging()),
            $logger
        );
    }
}
