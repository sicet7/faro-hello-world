<?php

namespace Sicet7\Faro\Core\Attributes;

use Attribute;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Factories\GenericFactory;
use Sicet7\Faro\Core\Interfaces\GenericFactoryInterface;

use function DI\factory;
use function DI\get;

#[Attribute(Attribute::TARGET_CLASS)]
class Definition
{
    /**
     * @var string[]
     */
    private array $definitionNames = [];

    /**
     * @var string
     */
    private string $factory;

    /**
     * @param string[] $definitionNames
     * @param string $factory
     * @throws ModuleException
     */
    public function __construct(array $definitionNames = [], string $factory = GenericFactory::class)
    {
        if (!empty($definitionNames)) {
            $this->definitionNames = array_unique(array_map(function (string $name) {
                return $name;
            }, $definitionNames));
        }
        if (!is_subclass_of($factory, GenericFactoryInterface::class)) {
            throw new ModuleException(
                'Invalid factory for definition: "' .
                $this->definitionNames[array_key_first($this->definitionNames)] . '".'
            );
        }
        $this->factory = $factory;
    }

    /**
     * @param string $foundOnFQCN
     * @return array
     */
    public function getDefinitions(string $foundOnFQCN): array
    {
        $output = [
            $foundOnFQCN => $this->getFactory(),
        ];
        if (empty($this->definitionNames)) {
            return $output;
        }
        foreach ($this->definitionNames as $name) {
            $output[$name] = get($foundOnFQCN);
        }
        return $output;
    }

    /**
     * @return FactoryDefinitionHelper
     */
    protected function getFactory(): FactoryDefinitionHelper
    {
        return factory([$this->factory, 'create']);
    }
}
