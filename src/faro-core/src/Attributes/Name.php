<?php

namespace Sicet7\Faro\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Name
{
    /**
     * @var string
     */
    private string $name;

    /**
     * CommandName constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
