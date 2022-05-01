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
     * @var string|null
     */
    private ?string $groupFqcn;

    /**
     * Route constructor.
     * @param array $methods
     * @param string $pattern
     * @param string|null $groupFqcn
     */
    public function __construct(
        array $methods,
        string $pattern,
        ?string $groupFqcn = null
    ) {
        $this->methods = $methods;
        $this->pattern = $pattern;
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
     * @return string|null
     */
    public function getGroupFqcn(): ?string
    {
        return $this->groupFqcn;
    }
}
