<?php

namespace Sicet7\Faro\Event;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
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
     * @var array[]
     */
    private array $listeners = [];

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * ListenerContainer constructor.
     * @param ContainerInterface $container
     * @param FactoryInterface $factory
     */
    public function __construct(
        ContainerInterface $container,
        FactoryInterface $factory
    ) {
        $this->container = $container;
        $this->factory = $factory;
    }

    /**
     * @param object $event
     * @return iterable
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getListenersForEvent(object $event): iterable
    {
        $fqn = get_class($event);
        if (!isset($this->listeners[$fqn]) || empty($this->listeners[$fqn])) {
            return [];
        }
        $listeners = [];
        foreach ($this->listeners[$fqn] as $listener => $makeNew) {
            $listeners[] = [
                ($makeNew ? $this->factory->make($listener) : $this->container->get($listener)),
                'execute',
            ];
        }
        return $listeners;
    }

    /**
     * @param string $listener
     * @return $this
     * @throws \ReflectionException|EventListenerException
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
        foreach ($reflectionClass->getAttributes(ListensTo::class) as $attribute) {
            if (
                ($attributeInstance = $attribute->newInstance()) instanceof ListensTo &&
                ($eventFqn = $attributeInstance->getEventFqn()) !== null &&
                class_exists($eventFqn)
            ) {
                /** @var ListensTo $attributeInstance */
                if (!array_key_exists($eventFqn, $this->listeners) || !is_array($this->listeners[$eventFqn])) {
                    $this->listeners[$eventFqn] = [];
                }
                $this->listeners[$eventFqn][$listener] = $attributeInstance->shouldMakeNew();
            }
        }
        return $this;
    }

    /**
     * @param string $listener
     * @return $this
     */
    public function removeListener(string $listener): ListenerProvider
    {
        foreach ($this->listeners as $event => $listeners) {
            if (isset($listeners[$listener])) {
                unset($this->listeners[$event][$listener]);
            }
        }
        return $this;
    }
}
