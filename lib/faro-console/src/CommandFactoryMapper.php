<?php

namespace Sicet7\Faro\Console;

use DI\Definition\Helper\FactoryDefinitionHelper;
use Sicet7\Faro\Core\Attributes\Name;
use Sicet7\Faro\Core\Exception\ContainerException;
use Symfony\Component\Console\Command\Command;

use function DI\factory;

class CommandFactoryMapper
{
    /**
     * @var array
     */
    private array $commandMap = [];

    /**
     * @param string $fqn
     * @return FactoryDefinitionHelper
     * @throws ContainerException
     */
    public function mapCommand(string $fqn): FactoryDefinitionHelper
    {
        if (!is_subclass_of($fqn, Command::class)) {
            throw new ContainerException(sprintf(
                'Cannot map command to non-command class. "%1$s" must extend "%2$s"',
                $fqn,
                Command::class
            ));
        }
        $reflectionClass = new \ReflectionClass($fqn);
        $nameAttributes = $reflectionClass->getAttributes(Name::class);
        if (count($nameAttributes) == 0) {
            throw new ContainerException(sprintf(
                'Missing a "%1$s" attribute for Command: "%2$s".',
                Name::class,
                $fqn
            ));
        }
        /** @var Name $nameAttributeInstance */
        $nameAttributeInstance = $nameAttributes[array_key_first($nameAttributes)]->newInstance();
        $name = $nameAttributeInstance->getName();
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
