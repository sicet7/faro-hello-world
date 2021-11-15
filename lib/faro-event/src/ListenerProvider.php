<?php

namespace Sicet7\Faro\Event;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionException;
use Sicet7\Faro\Core\BuildLock;
use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Exceptions\EventListenerException;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;
use Sicet7\Faro\Event\Interfaces\ListenerProviderInterface;
use Sicet7\Faro\Event\Interfaces\ListensToInterface;
use Throwable;

class ListenerProvider implements ListenerProviderInterface
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
     * @var BuildLock
     */
    private BuildLock $buildLock;

    /**
     * ListenerContainer constructor.
     * @param ContainerInterface $container
     * @param BuildLock $buildLock
     */
    public function __construct(
        ContainerInterface $container,
        BuildLock $buildLock
    ) {
        $this->container = $container;
        $this->buildLock = $buildLock;
    }

    /**
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        $fqcn = get_class($event);
        if (!$this->hasListenersForEvent($event)) {
            return [];
        }
        $listeners = [];
        foreach ($this->listeners[$fqcn] as $id => $listener) {
            $listeners[] = [
                (is_string($listener) ? $this->container->get($listener) : $listener),
                'execute',
            ];
        }
        return $listeners;
    }

    /**
     * @param object $event
     * @return bool
     */
    public function hasListenersForEvent(object $event): bool
    {
        $eventFqcn = get_class($event);
        return (isset($this->listeners[$eventFqcn]) && !empty($this->listeners[$eventFqcn]));
    }

    /**
     * @param string|ListenerInterface $listener
     * @return $this
     * @throws Throwable|EventListenerException
     */
    public function addListener(string|ListenerInterface $listener): ListenerProvider
    {
        $this->lockCheck();
        if (is_string($listener)) {
            if (!is_subclass_of($listener, ListenerInterface::class)) {
                throw new EventListenerException(
                    '"' . $listener . '" does not implement: "' . ListenerInterface::class . '"'
                );
            }
            if (!$this->container->has($listener)) {
                throw new EventListenerException('Event Listener: "' . $listener . '" not found in container.');
            }
            $id = $listener;
        } else {
            $id = spl_object_id($listener);
        }
        $reflectionClass = new \ReflectionClass($listener);
        foreach ($reflectionClass->getAttributes(ListensTo::class) as $attribute) {
            if (
                ($attributeInstance = $attribute->newInstance()) instanceof ListensTo &&
                ($eventFqcn = $attributeInstance->getEventFqcn()) !== null &&
                class_exists($eventFqcn)
            ) {
                /** @var ListensTo $attributeInstance */
                if (
                    !array_key_exists($eventFqcn, $this->listeners) ||
                    !is_array($this->listeners[$eventFqcn])
                ) {
                    $this->listeners[$eventFqcn] = [];
                }
                $this->listeners[$eventFqcn][$id] = $listener;
            }
        }
        if (!is_string($listener) && $listener instanceof ListensToInterface) {
            foreach ($listener->getEvents() as $eventFqcn) {
                if (class_exists($eventFqcn)) {
                    $this->listeners[$eventFqcn][$id] = $listener;
                }
            }
        }
        return $this;
    }

    /**
     * @param string|ListenerInterface $listener
     * @return $this
     * @throws Throwable
     */
    public function removeListener(string|ListenerInterface $listener): ListenerProvider
    {
        $this->lockCheck();
        $id = (is_string($listener) ? $listener : spl_object_id($listener));
        foreach ($this->listeners as $event => $listeners) {
            if (isset($listeners[$id])) {
                unset($this->listeners[$event][$id]);
            }
        }
        return $this;
    }

    /**
     * @throws \Throwable
     * @return void
     */
    private function lockCheck(): void
    {
        $this->buildLock->throwIfLocked(new EventListenerException(
            'You cannot modify the Listener Stack at Runtime.'
        ));
    }
}
