<?php

namespace Sicet7\Faro\Event\Interfaces;

interface ListensToInterface
{
    /**
     * @return string[]
     */
    public function getEvents(): array;
}
