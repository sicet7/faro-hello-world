<?php

namespace Sicet7\Faro\Event\Interfaces;

interface ListenerInterface
{
    /**
     * @param object $event
     * @return void
     */
    public function execute(object $event): void;
}
