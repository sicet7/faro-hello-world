<?php

namespace Sicet7\Faro\Core\Event;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\EventListenerException;

class ListenerContainer implements ListenerContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var array[]
     */
    private array $listeners = [];

    /**
     * ListenerContainer constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getListenersForEvent(object $event): iterable
    {
        $fqn = $this->parseEventFqn(get_class($event));
        $listeners = $this->listeners[$fqn] ?? [];
        $returnArray = [];
        if (empty($listeners)) {
            return [];
        }
        foreach ($listeners as $listener) {
            if (!is_subclass_of($listener, ListenerInterface::class)) {
                continue;
            }
            $returnArray[] = [
                $this->container->get($listener),
                'execute'
            ];
        }
        return $returnArray;
    }

    /**
     * @inheritDoc
     * @throws EventListenerException
     */
    public function addListener(string $eventFqn, string $listener, string $identifier): ListenerContainer
    {
        $eventFqn = $this->parseEventFqn($eventFqn);
        if (!isset($this->listeners[$eventFqn]) || !is_array($this->listeners[$eventFqn])) {
            $this->listeners[$eventFqn] = [];
        }
        if (!is_subclass_of($listener, ListenerInterface::class)) {
            throw new EventListenerException(
                'Event Listener must be subclass of: "' . ListenerInterface::class . "\": \"$listener\" is not."
            );
        }
        if (!$this->container->has($listener)) {
            throw new EventListenerException("Event Listener: \"$listener\" not found in container.");
        }
        if (isset($this->listeners[$eventFqn][$identifier])) {
            throw new EventListenerException("Identifier already exists: \"$identifier\".");
        }
        $this->listeners[$eventFqn][$identifier] = $listener;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeListener(string $eventFqn, string $identifier): ListenerContainer
    {
        $eventFqn = $this->parseEventFqn($eventFqn);
        if (isset($this->listeners[$eventFqn][$identifier])) {
            unset($this->listeners[$eventFqn][$identifier]);
        }
        return $this;
    }

    /**
     * @param string $eventFqn
     * @return string
     */
    protected function parseEventFqn(string $eventFqn): string
    {
        return trim($eventFqn, "\\ \t\n\r\0\x0B");
    }
}
