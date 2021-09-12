<?php

declare(strict_types=1);

namespace Sicet7\Faro\Config\Definitions;

use Sicet7\Faro\Config\ConfigMap;
use Sicet7\Faro\Config\Exceptions\ConfigException;

class ConcatDefinition implements VariableDefinitionInterface
{
    private array $concatItems;

    /**
     * ConcatDefinition constructor.
     * @param array $concatItems
     */
    public function __construct(array $concatItems = [])
    {
        $this->concatItems = $concatItems;
    }

    /**
     * @param ConfigMap $configMap
     * @return mixed
     * @throws ConfigException
     */
    public function resolve(ConfigMap $configMap): mixed
    {
        $resolvedItems = [];
        foreach ($this->concatItems as $key => $concatItem) {
            if ($concatItem instanceof VariableDefinitionInterface) {
                $concatItem = $concatItem->resolve($configMap);
            }
            $resolvedItems[$key] = $concatItem;
        }

        $concatItem = $this;

        foreach ($resolvedItems as $item) {
            if ($concatItem instanceof ConcatDefinition) {
                $concatItem = $item;
                continue;
            }
            if (is_string($concatItem)) {
                if (is_array($item)) {
                    throw new ConfigException('Cannot concat array to string.');
                }
                $concatItem .= $item;
            } elseif (is_array($concatItem)) {
                if (is_array($item)) {
                    foreach ($item as $value) {
                        $concatItem[] = $value;
                    }
                } else {
                    $concatItem[] = $item;
                }
            } else {
                throw new ConfigException('Failed to determine ConcatItem type.');
            }
        }

        if ($concatItem instanceof ConcatDefinition) {
            return null;
        }
        return $concatItem;
    }
}
