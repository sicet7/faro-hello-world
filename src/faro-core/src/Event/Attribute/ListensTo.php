<?php

namespace Sicet7\Faro\Core\Event\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ListensTo
{
    private string $eventFqn;

    /**
     * ListensTo constructor.
     * @param string $eventFqn
     */
    public function __construct(
        string $eventFqn
    ) {
        $this->eventFqn = $eventFqn;
    }

    /**
     * @return string
     */
    public function getEventFqn(): string
    {
        return $this->eventFqn;
    }
}
