<?php

namespace Sicet7\Faro\Event\Interfaces;

interface HasListenersInterface
{
    /**
     * @return array
     */
    public static function getListeners(): array;
}
