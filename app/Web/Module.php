<?php

namespace App\Web;

use Sicet7\Faro\Core\BaseModule;
use Sicet7\Faro\Core\Tools\PSR4;
use Sicet7\Faro\Slim\Interfaces\HasRoutesInterface;

class Module extends BaseModule implements HasRoutesInterface
{
    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            \Sicet7\Faro\Slim\Module::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getRoutes(): array
    {
        return PSR4::getFQCNs('App\\Web\\Routes', __DIR__ . '/Routes');
    }
}
