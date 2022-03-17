<?php

namespace Sicet7\Faro\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class Dispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface
     */
    private ListenerProviderInterface $listenerContainer;

    /**
     * Dispatcher constructor.
     * @param ListenerProviderInterface $listenerContainer
     */
    public function __construct(ListenerProviderInterface $listenerContainer)
    {
        $this->listenerContainer = $listenerContainer;
    }

    /**
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        return $this->callListeners($this->listenerContainer->getListenersForEvent($event), $event);
    }

    /**
     * @param iterable $listeners
     * @param object $event
     * @return object
     */
    protected function callListeners(iterable $listeners, object $event): object
    {
        $canStop = $event instanceof StoppableEventInterface;
        foreach ($listeners as $listener) {
            if ($canStop && $event->isPropagationStopped()) {
                break;
            }
            $listener($event);
        }
        return $event;
    }
}
