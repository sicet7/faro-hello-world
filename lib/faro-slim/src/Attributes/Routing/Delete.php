<?php

namespace Sicet7\Faro\Slim\Attributes\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Delete extends Route
{
    /**
     * Delete constructor.
     * @param string $pattern
     * @param array $middlewares
     * @param string|null $groupFqcn
     */
    public function __construct(
        string $pattern,
        array $middlewares = [],
        ?string $groupFqcn = null
    ) {
        parent::__construct(['DELETE'], $pattern, $middlewares, $groupFqcn);
    }
}
