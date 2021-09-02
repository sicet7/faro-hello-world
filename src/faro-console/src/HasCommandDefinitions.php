<?php

namespace Sicet7\Faro\Console;

interface HasCommandDefinitions
{
    /**
     * Structure of the array should be: ["some:command:name" => \MyCommand::class]
     *
     * @return array
     */
    public static function getCommandDefinitions(): array;
}
