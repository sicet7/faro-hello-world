<?php

namespace Sicet7\Faro\Event\Interfaces;

interface ListenerProviderInterface extends \Psr\EventDispatcher\ListenerProviderInterface
{
    /**
     * @param string $listener
     * @return ListenerProviderInterface
     */
    public function addListener(string $listener): ListenerProviderInterface;

    /**
     * @param string $listener
     * @return ListenerProviderInterface
     */
    public function removeListener(string $listener): ListenerProviderInterface;
}
