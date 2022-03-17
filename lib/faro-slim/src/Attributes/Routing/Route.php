<?php

namespace Sicet7\Faro\Slim\Attributes\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Route
{
    /**
     * @var array
     */
    private array $methods;

    /**
     * @var string
     */
    private string $pattern;

    /**
     * @var string[]
     */
    private array $middlewares;

    /**
     * @var string|null
     */
    private ?string $groupFqcn;

    /**
     * Route constructor.
     * @param array $methods
     * @param string $pattern
     * @param array $middlewares
     * @param string|null $groupFqcn
     */
    public function __construct(
        array $methods,
        string $pattern,
        array $middlewares = [],
        ?string $groupFqcn = null
    ) {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->middlewares = $middlewares;
        $this->groupFqcn = $groupFqcn;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return string[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @return string|null
     */
    public function getGroupFqcn(): ?string
    {
        return $this->groupFqcn;
    }
}
