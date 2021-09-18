<?php

namespace App\Console\Commands;

use Sicet7\Faro\Core\Attributes\Name;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Name('hello:world')]
class HelloWorldCommand extends Command
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello World!');
        return 0;
    }
}
