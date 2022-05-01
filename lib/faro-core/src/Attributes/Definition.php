<?php

namespace Sicet7\Faro\Core\Attributes;

use Attribute;
use DI\Definition\Helper\FactoryDefinitionHelper;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\Factories\DefaultFactory;
use Sicet7\Faro\Core\Factories\GenericFactory;
use Sicet7\Faro\Core\Interfaces\GenericFactoryInterface;

use function DI\factory;
use function DI\get;

#[Attribute(Attribute::TARGET_CLASS)]
class Definition
{
    /**
     * @var array
     */
    private array $parameters;

    /**
     * @var string[]
     */
    private array $definitionNames = [];

    /**
     * @var string
     */
    private string $factory;

    /**
     * @param array $parameters
     * @param string[] $definitionNames
     * @param string $factory
     * @throws ModuleException
     */
    public function __construct(
        array $parameters = [],
        array $definitionNames = [],
        string $factory = DefaultFactory::class
    ) {
        $this->parameters = $parameters;
        if (!empty($definitionNames)) {
            $this->definitionNames = array_unique(array_map(function (string $name) {
                return $name;
            }, $definitionNames));
        }
        if (!method_exists($factory, 'create')) {
            throw new ModuleException(
                'Invalid factory. "create" method missing. for definition: "' .
                $this->definitionNames[array_key_first($this->definitionNames)] . '". '
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
        $factory = factory([$this->factory, 'create']);
        foreach ($this->parameters as $name => $parameter) {
            $factory->parameter($name, $parameter);
        }
        return $factory;
    }
}
