<?php

namespace Sicet7\Faro\Core\Event;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Event\Attribute\ListensTo;
use Sicet7\Faro\Core\Exception\EventListenerException;

class ListenerContainer implements ListenerContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var string[]
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
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        $fqn = get_class($event);
        $listeners = [];
        foreach ($this->listeners as $listener => $eventFqn) {
            if ($fqn == $eventFqn) {
                $listeners[] = [
                    $this->container->get($listener),
                    'execute'
                ];
            }
        }
        return $listeners;
    }

    /**
     * @param string $listenerFqn
     * @return $this
     * @throws EventListenerException
     */
    public function addListener(string $listenerFqn): ListenerContainer
    {
        if (!is_subclass_of($listenerFqn, ListenerInterface::class)) {
            throw new EventListenerException(
                '"' . $listenerFqn . '" does not implement: "' . ListenerInterface::class . '"'
            );
        }
        if (!$this->container->has($listenerFqn)) {
            throw new EventListenerException('Event Listener: "' . $listenerFqn . '" not found in container.');
        }
        $reflectionClass = new \ReflectionClass($listenerFqn);
        $eventFqn = null;
        foreach ($reflectionClass->getAttributes(ListensTo::class) as $attribute) {
            $attributeInstance = $attribute->newInstance();
            if ($attributeInstance instanceof ListensTo) {
                $eventFqn = $attributeInstance->getEventFqn();
                break;
            }
        }
        if ($eventFqn !== null && class_exists($eventFqn)) {
            $this->listeners[$listenerFqn] = $eventFqn;
        }
        return $this;
    }

    /**
     * @param string $listenerFqn
     * @return $this
     */
    public function removeListener(string $listenerFqn): ListenerContainer
    {
        if (array_key_exists($listenerFqn, $this->listeners)) {
            unset($this->listeners[$listenerFqn]);
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
