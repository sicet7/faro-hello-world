<?php

namespace Sicet7\Faro\Console;

use DI\Definition\Helper\FactoryDefinitionHelper;
use ReflectionException;
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
     * @param string|null $commandName
     * @return FactoryDefinitionHelper[]
     * @throws ReflectionException|ContainerException
     */
    public function mapCommand(string $fqn, ?string $commandName = null): array
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
        $names = [];

        if ($commandName !== null) {
            $names[] = $commandName;
        }

        if (count($nameAttributes) > 0) {
            $names = array_merge(
                $names,
                explode('|', $nameAttributes[array_key_first($nameAttributes)]->newInstance()->getName())
            );
        }

        $names = array_merge(
            $names,
            explode('|', (!empty($name = $fqn::getDefaultName()) ? $name : ''))
        );

        $names = array_unique(array_filter($names, function ($v) {
            return !empty(trim($v));
        }));

        if (empty($names)) {
            throw new ContainerException(sprintf(
                'Missing a name for Command: "%1$s".',
                $fqn
            ));
        }

        $definitions = [];
        foreach ($names as $commandAlias) {
            if (empty(trim($commandAlias))) {
                continue;
            }
            if (empty($definitions)) {
                $definitions[$fqn] = factory([CommandFactory::class, 'create'])
                    ->parameter('name', $commandAlias);
            }
            $this->commandMap[$commandAlias] = $fqn;
        }
        return $definitions;
    }

    /**
     * @return array
     */
    public function getMap(): array
    {
        return $this->commandMap;
    }
}
