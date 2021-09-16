<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

use DI\DependencyException;
use DI\Factory\RequestedEntry;
use Sicet7\Faro\Core\Factories\GenericFactory;
use Symfony\Component\Console\Command\Command;

class CommandFactory extends GenericFactory
{
    /**
     * @var array
     */
    private array $providedParameters = [];

    /**
     * @param RequestedEntry $entry
     * @param string|null $name
     * @return object
     * @throws DependencyException
     */
    public function create(RequestedEntry $entry, string $name = null): object
    {
        $this->providedParameters = [];
        if (!empty($name)) {
            $this->providedParameters['name'] = $name;
        }
        return parent::create($entry);
    }

    /**
     * @return array
     */
    protected function getProvidedParameters(): array
    {
        return $this->providedParameters;
    }

    /**
     * @return array
     */
    protected function getResolvedParameters(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function getClassWhitelist(): array
    {
        return [
            Command::class
        ];
    }
}
