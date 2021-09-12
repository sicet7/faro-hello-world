<?php

namespace Sicet7\Faro\Core\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

interface ListenerContainerInterface extends ListenerProviderInterface
{
    /**
     * @param string $listenerFqn
     * @return ListenerContainerInterface
     */
    public function addListener(string $listenerFqn): ListenerContainerInterface;

    /**
     * @param string $listenerFqn
     * @return ListenerContainerInterface
     */
    public function removeListener(string $listenerFqn): ListenerContainerInterface;
}
