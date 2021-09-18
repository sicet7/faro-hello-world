<?php

namespace Sicet7\Faro\Event;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Exceptions\EventListenerException;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;
use Sicet7\Faro\Event\Interfaces\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
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
                    'execute',
                ];
            }
        }
        return $listeners;
    }

    /**
     * @param string $listener
     * @return $this
     * @throws EventListenerException|\ReflectionException
     */
    public function addListener(string $listener): ListenerProvider
    {
        if (!is_subclass_of($listener, ListenerInterface::class)) {
            throw new EventListenerException(
                '"' . $listener . '" does not implement: "' . ListenerInterface::class . '"'
            );
        }
        if (!$this->container->has($listener)) {
            throw new EventListenerException('Event Listener: "' . $listener . '" not found in container.');
        }
        $reflectionClass = new \ReflectionClass($listener);
        $eventFqn = null;
        foreach ($reflectionClass->getAttributes(ListensTo::class) as $attribute) {
            $attributeInstance = $attribute->newInstance();
            if ($attributeInstance instanceof ListensTo) {
                $eventFqn = $attributeInstance->getEventFqn();
                break;
            }
        }
        if ($eventFqn !== null && class_exists($eventFqn)) {
            $this->listeners[$listener] = $eventFqn;
        }
        return $this;
    }

    /**
     * @param string $listener
     * @return $this
     */
    public function removeListener(string $listener): ListenerProvider
    {
        if (array_key_exists($listener, $this->listeners)) {
            unset($this->listeners[$listener]);
        }
        return $this;
    }
}
