<?php

declare(strict_types=1);

namespace Sicet7\Faro\Config\Commands;

use Sicet7\Faro\Config\ConfigMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class ShowCommand extends Command
{

    /**
     * @var ConfigMap
     */
    private ConfigMap $configMap;

    /**
     * ShowCommand constructor.
     * @param ConfigMap $configMap
     * @param string|null $name
     */
    public function __construct(
        ConfigMap $configMap,
        string $name = null
    ) {
        parent::__construct($name);
        $this->configMap = $configMap;
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
            $dumper->dump($cloner->cloneVar($this->configMap->readMap()));
        } else {
            $dumper->dump($cloner->cloneVar($this->configMap->get($path)));
        }

        return static::SUCCESS;
    }
}
