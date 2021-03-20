<?php

namespace Sicet7\Faro\Swoole\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello2');
        return 0;
    }
}
