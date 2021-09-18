<?php

namespace Sicet7\Faro\Console;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\ModuleContainer as BaseModuleContainer;

class ModuleContainer extends BaseModuleContainer
{
    /**
     * @param array $customDefinitions
     * @return ContainerInterface
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ModuleException
     */
    public static function buildContainer(array $customDefinitions = []): ContainerInterface
    {
        if (class_exists('App\\Console\\Module')) {
            static::tryRegisterModule('App\\Console\\Module');
        }
        return parent::buildContainer($customDefinitions);
    }
}
