<?php

namespace Sicet7\Faro\Slim\Interfaces;

interface RouteGroupInterface
{
    /**
     * @return string
     */
    public static function getPattern(): string;

    /**
     * @return string[]
     */
    public static function getMiddlewares(): array;
}
