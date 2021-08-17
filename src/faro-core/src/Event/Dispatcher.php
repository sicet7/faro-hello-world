<?php

namespace Sicet7\Faro\Core\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class Dispatcher implements EventDispatcherInterface
{

    /**
     * @var ListenerContainer
     */
    private ListenerContainer $listenerContainer;

    public function __construct(ListenerContainer $listenerContainer)
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
    public function callListeners(iterable $listeners, object $event): object
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
