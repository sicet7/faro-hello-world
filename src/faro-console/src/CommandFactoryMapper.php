<?php

namespace Sicet7\Faro\Console;

use DI\Definition\Helper\FactoryDefinitionHelper;

use function DI\factory;

class CommandFactoryMapper
{
    /**
     * @var array
     */
    private array $commandMap = [];

    /**
     * @param string $name
     * @param string $fqn
     * @return FactoryDefinitionHelper
     */
    public function mapCommand(string $name, string $fqn): FactoryDefinitionHelper
    {
        $this->commandMap[$name] = $fqn;
        return factory([CommandFactory::class, 'create'])
            ->parameter('name', $name);
    }

    /**
     * @return array
     */
    public function getMap(): array
    {
        return $this->commandMap;
    }
}
