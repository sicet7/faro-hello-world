<?php

namespace Sicet7\Faro\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ListensTo
{
    /**
     * @var string
     */
    private string $eventFqn;

    /**
     * @var bool
     */
    private bool $newInstance;

    /**
     * ListensTo constructor.
     * @param string $eventFqn
     * @param bool $newInstance true, if a new instance of the listener should be made for every event.
     */
    public function __construct(
        string $eventFqn,
        bool $newInstance = false
    ) {
        $this->eventFqn = $eventFqn;
        $this->newInstance = $newInstance;
    }

    /**
     * @return string
     */
    public function getEventFqn(): string
    {
        return $this->eventFqn;
    }

    /**
     * @return bool
     */
    public function getNewInstance(): bool
    {
        return $this->newInstance;
    }
}
