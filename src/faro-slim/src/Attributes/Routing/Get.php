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
     * @param string|null $groupFqn
     */
    public function __construct(
        string $pattern,
        array $middlewares = [],
        ?string $groupFqn = null
    ) {
        parent::__construct(['GET'], $pattern, $middlewares, $groupFqn);
    }
}
