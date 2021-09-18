<?php

namespace Sicet7\Faro\Web;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Core\Exception\ModuleException;
use Sicet7\Faro\Core\ModuleContainer as BaseModuleContainer;
use Sicet7\Faro\Event\Module as EventModule;

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
        if (class_exists('App\\Web\\Module')) {
            static::tryRegisterModule('App\\Web\\Module');
        }
        static::tryRegisterModule(EventModule::class);
        return parent::buildContainer($customDefinitions);
    }
}
