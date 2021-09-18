<?php

namespace Sicet7\Faro\Slim\Factories;

use Sicet7\Faro\Core\Factories\GenericFactory;
use Sicet7\Faro\Slim\Exceptions\RouteLockException;

class RouteFactory extends GenericFactory
{
    /**
     * @var array
     */
    private array $classWhitelist = [];

    /**
     * @var bool
     */
    private bool $locked = false;

    /**
     * @param string $fqn
     * @return bool
     */
    protected function inWhitelist(string $fqn): bool
    {
        if (empty($this->getClassWhitelist())) {
            return false;
        }
        return parent::inWhitelist($fqn);
    }

    /**
     * @return array
     */
    protected function getProvidedParameters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getResolvedParameters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getClassWhitelist(): array
    {
        return $this->classWhitelist;
    }

    /**
     * @param array $classWhitelist
     * @return void
     * @throws RouteLockException
     */
    public function setClassWhitelist(array $classWhitelist): void
    {
        if ($this->locked) {
            throw new RouteLockException('Cannot modify route factory whitelist after initial value.');
        }
        $this->classWhitelist = $classWhitelist;
        $this->locked = true;
    }
}
