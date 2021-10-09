<?php

namespace Sicet7\Faro\Event\Interfaces;

interface ListenerProviderInterface extends \Psr\EventDispatcher\ListenerProviderInterface
{
    /**
     * @param object $event
     * @return bool
     */
    public function hasListenersForEvent(object $event): bool;

    /**
     * @param string|ListenerInterface $listener
     * @return ListenerProviderInterface
     */
    public function addListener(string|ListenerInterface $listener): ListenerProviderInterface;

    /**
     * @param string|ListenerInterface $listener
     * @return ListenerProviderInterface
     */
    public function removeListener(string|ListenerInterface $listener): ListenerProviderInterface;
}
