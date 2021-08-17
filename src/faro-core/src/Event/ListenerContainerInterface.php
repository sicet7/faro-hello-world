<?php

namespace Sicet7\Faro\Core\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

interface ListenerContainerInterface extends ListenerProviderInterface
{
    /**
     * @param string $eventFqn
     * @param string $listener
     * @param string $identifier
     * @return ListenerContainerInterface
     */
    public function addListener(string $eventFqn, string $listener, string $identifier): ListenerContainerInterface;

    /**
     * @param string $eventFqn
     * @param string $identifier
     * @return ListenerContainerInterface
     */
    public function removeListener(string $eventFqn, string $identifier): ListenerContainerInterface;
}
