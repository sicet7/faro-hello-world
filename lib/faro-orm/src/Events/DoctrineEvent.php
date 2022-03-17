<?php

namespace Sicet7\Faro\ORM\Events;

use Doctrine\Common\EventArgs;

abstract class DoctrineEvent
{
    /**
     * @var EventArgs|null
     */
    private ?EventArgs $eventArgs;

    /**
     * DoctrineEvent constructor.
     * @param EventArgs|null $eventArgs
     */
    public function __construct(?EventArgs $eventArgs = null)
    {
        $this->eventArgs = $eventArgs;
    }

    /**
     * @return EventArgs|null
     */
    public function getEventArgs(): ?EventArgs
    {
        return $this->eventArgs;
    }
}
