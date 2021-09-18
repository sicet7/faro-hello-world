<?php

namespace App\Web;

use App\Web\Routes\ConfigAction;
use App\Web\Routes\HelloWorld;
use App\Web\Routes\Ping;
use Sicet7\Faro\Core\AbstractModule;
use Sicet7\Faro\Slim\Interfaces\HasRoutesInterface;

class Module extends AbstractModule implements HasRoutesInterface
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'web-app';
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            'faro-slim'
        ];
    }

    /**
     * @return string[]
     */
    public static function getRoutes(): array
    {
        return [
            HelloWorld::class,
            Ping::class,
            ConfigAction::class,
        ];
    }
}
