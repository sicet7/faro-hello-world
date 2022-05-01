<?php

namespace Sicet7\Faro\Slim\Attributes\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @var string
     */
    private string $middlewareFQCN;

    /**
     * @param string $middlewareFQCN
     */
    public function __construct(string $middlewareFQCN)
    {
        $this->middlewareFQCN = $middlewareFQCN;
    }

    /**
     * @return string
     */
    public function getMiddlewareFQCN(): string
    {
        return $this->middlewareFQCN;
    }
}
