<?php

namespace Sicet7\Faro\Core\Interfaces;

use DI\Factory\RequestedEntry;

interface GenericFactoryInterface
{
    /**
     * @param RequestedEntry $entry
     * @return object
     */
    public function create(RequestedEntry $entry): object;
}
