<?php

namespace Sicet7\Faro\Console\Interfaces;

interface HasCommandsInterface
{
    /**
     * Should be a list of Command FQN's : [\MyCommand::class]
     *
     * @return array
     */
    public static function getCommands(): array;
}
