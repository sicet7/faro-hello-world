<?php

namespace Sicet7\Faro\Slim;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Sicet7\Faro\Config\ConfigMap;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Core\Event\ListenerContainerInterface;
use Sicet7\Faro\Slim\Listeners\RequestListener;
use Sicet7\Faro\Web\RequestEvent;
use Slim\App;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use function DI\create;
use function DI\get;

class Module extends AbstractModule
{

    public static function getName(): string
    {
        return 'slim';
    }

    public static function getDefinitions(): array
    {
        return [
            App::class => function (ContainerInterface $container) {
                $app = AppFactory::createFromContainer($container);
                $app->get('/', function(Request $request, Response $response) use ($container) {
                    $response->getBody()->write(var_export($container->get(ConfigMap::class)->readMap(), true));
                    return $response;
                });
                return $app;
            },
            Psr17Factory::class => create(Psr17Factory::class),
            ResponseFactoryInterface::class => get(Psr17Factory::class),
            RequestListener::class => create(RequestListener::class)
                ->constructor(get(App::class)),
        ];
    }

    public static function setup(ContainerInterface $container): void
    {
        $container->get(ListenerContainerInterface::class)->addListener(
            RequestEvent::class,
            RequestListener::class,
            'slim.request.listener'
        );
    }
}
