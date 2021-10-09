<?php

namespace Sicet7\Faro\ORM;

class AttributeDriver extends \Doctrine\ORM\Mapping\Driver\AttributeDriver
{
    /**
     * @var string[]
     */
    private array $classes = [];

    /**
     * @param array $paths
     * @return void
     */
    public function addExcludePaths(array $paths)
    {
        // Do Nothing.
    }

    /**
     * @param array $paths
     * @return void
     */
    public function addPaths(array $paths)
    {
        // Do Nothing.
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getExcludePaths()
    {
        return [];
    }

    /**
     * @param string $classFQCN
     * @return void
     */
    public function addClass(string $classFQCN): void
    {
        if (!in_array($classFQCN, $this->classes)) {
            $this->classes[] = $classFQCN;
        }
    }

    /**
     * @return array|string[]|null
     */
    public function getAllClassNames()
    {
        return $this->classes;
    }
}
