<?php


namespace Sicet7\Faro\Event\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ListensTo
{
    /**
     * @var string
     */
    private string $eventFqn;

    /**
     * @var bool
     */
    private bool $new;

    /**
     * ListensTo constructor.
     * @param string $eventFqn
     * @param bool $new true, if a new instance of the listener should be made for every event.
     */
    public function __construct(
        string $eventFqn,
        bool $new = false
    ) {
        $this->eventFqn = $eventFqn;
        $this->new = $new;
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
    public function shouldMakeNew(): bool
    {
        return $this->new;
    }
}
