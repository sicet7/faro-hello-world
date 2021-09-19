<?php

namespace Sicet7\Faro\Slim\Interfaces;

use Slim\Interfaces\InvocationStrategyInterface;

interface ControllerInvocationStrategyFactoryInterface
{
    /**
     * @return InvocationStrategyInterface
     */
    public function create(): InvocationStrategyInterface;
}
