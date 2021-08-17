<?php

namespace Sicet7\Faro\Web;

use DI\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;

class WebContainer extends Container
{
    /**
     * @param RequestInterface $request
     * @return $this
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function dispatchRequestEvent(RequestInterface $request): WebContainer
    {
        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher->dispatch(new RequestEvent($request));
        return $this;
    }
}
