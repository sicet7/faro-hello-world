<?php

namespace Sicet7\Faro\Core\Interfaces;

interface HasListenersInterface
{
    /**
     * @return array
     */
    public static function getListeners(): array;
}
