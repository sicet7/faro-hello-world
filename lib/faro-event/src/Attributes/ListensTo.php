<?php

namespace Sicet7\Faro\Event\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ListensTo
{
    /**
     * @var string
     */
    private string $eventFqcn;

    /**
     * ListensTo constructor.
     * @param string $eventFqcn
     */
    public function __construct(
        string $eventFqcn
    ) {
        $this->eventFqcn = $eventFqcn;
    }

    /**
     * @return string
     */
    public function getEventFqcn(): string
    {
        return $this->eventFqcn;
    }
}
