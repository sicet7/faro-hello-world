<?php

namespace Sicet7\Faro\Console\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;

class SymfonyDispatcher implements SymfonyEventDispatcherInterface
{
    /**
     * @var PsrEventDispatcherInterface
     */
    private PsrEventDispatcherInterface $dispatcher;

    /**
     * SymfonyDispatcher constructor.
     * @param PsrEventDispatcherInterface $dispatcher
     */
    public function __construct(PsrEventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event, string $eventName = null): object
    {
        return $this->dispatcher->dispatch($event);
    }
}
