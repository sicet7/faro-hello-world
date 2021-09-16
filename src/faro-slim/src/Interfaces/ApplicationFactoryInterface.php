<?php

namespace Sicet7\Faro\Slim\Interfaces;

use Slim\App;

interface ApplicationFactoryInterface
{
    /**
     * @return App
     */
    public function create(): App;
}
