<?php

namespace Sicet7\Faro\Slim;

use Invoker\InvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class ControllerInvocationStrategy implements InvocationStrategyInterface
{
    /**
     * @var InvokerInterface
     */
    private InvokerInterface $invoker;

    /**
     * ControllerInvoker constructor.
     * @param InvokerInterface $invoker
     */
    public function __construct(InvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * @param callable $callable
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $routeArguments
     * @return ResponseInterface
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        $args = [
            'request'  => $request,
            'response' => $response,
            'routeArguments' => $routeArguments,
        ];
        $args += $routeArguments;
        return $this->invoker->call(
            $callable,
            $args
        );
    }
}
