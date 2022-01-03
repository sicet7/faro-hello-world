<?php

namespace App\Console\Commands;

use App\Database\Entities\TestEntity;
use App\Database\Repositories\TestRepository;
use Sicet7\Faro\Core\Attributes\Name;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Name('new:entry')]
class NewEntry extends Command
{
    /**
     * @var TestRepository
     */
    private TestRepository $testRepository;

    /**
     * @param TestRepository $testRepository
     * @param string|null $name
     */
    public function __construct(TestRepository $testRepository, string $name = null)
    {
        parent::__construct($name);
        $this->testRepository = $testRepository;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'name of the entry');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $entry = new TestEntity($name);
        $this->testRepository->save($entry);
        return Command::SUCCESS;
    }
}
