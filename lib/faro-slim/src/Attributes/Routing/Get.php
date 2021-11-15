<?php

namespace Sicet7\Faro\Slim\Attributes\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Get extends Route
{
    /**
     * Get constructor.
     * @param string $pattern
     * @param string[] $middlewares
     * @param string|null $groupFqcn
     */
    public function __construct(
        string $pattern,
        array $middlewares = [],
        ?string $groupFqcn = null
    ) {
        parent::__construct(['GET'], $pattern, $middlewares, $groupFqcn);
    }
}
