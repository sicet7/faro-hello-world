<?php

declare(strict_types=1);

namespace Sicet7\Faro\Config\Console\Commands;

use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\Attributes\Name;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;

#[Name('config:show')]
class ShowCommand extends Command
{

    /**
     * @var Config
     */
    private Config $config;

    /**
     * ShowCommand constructor.
     * @param Config $config
     * @param string|null $name
     */
    public function __construct(
        Config $config,
        string $name = null
    ) {
        parent::__construct($name);
        $this->config = $config;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path of the config you want to be shown'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \ErrorException
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $dumper = new CliDumper(null, 'UTF-8', AbstractDumper::DUMP_TRAILING_COMMA);
        $cloner = new VarCloner();

        $path = $input->getArgument('path');
        if (empty($path)) {
            $dumper->dump($cloner->cloneVar($this->config->getConfig()));
        } else {
            $dumper->dump($cloner->cloneVar($this->config->get($path)));
        }

        return static::SUCCESS;
    }
}
