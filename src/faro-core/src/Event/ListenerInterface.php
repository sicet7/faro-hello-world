<?php

namespace Sicet7\Faro\Core\Event;

interface ListenerInterface
{
    /**
     * @param object $event
     * @return void
     */
    public function execute(object $event): void;
}
