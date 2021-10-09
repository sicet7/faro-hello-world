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
