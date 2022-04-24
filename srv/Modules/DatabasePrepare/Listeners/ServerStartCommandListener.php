<?php

namespace Server\Modules\DatabasePrepare\Listeners;

use Server\Modules\Core\Environment;
use Server\Modules\DatabasePrepare\HasMigrationsCheck;
use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;
use Sicet7\Faro\Swoole\Commands\StartCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;

#[ListensTo(ConsoleCommandEvent::class)]
class ServerStartCommandListener implements ListenerInterface
{
    /**
     * @var Environment
     */
    private Environment $environment;

    /**
     * @var Application
     */
    private Application $application;

    /**
     * @var HasMigrationsCheck
     */
    private HasMigrationsCheck $migrationsCheck;

    /**
     * @param Environment $environment
     * @param Application $application
     * @param HasMigrationsCheck $migrationsCheck
     */
    public function __construct(
        Environment $environment,
        Application $application,
        HasMigrationsCheck $migrationsCheck
    ) {
        $this->environment = $environment;
        $this->application = $application;
        $this->migrationsCheck = $migrationsCheck;
    }

    /**
     * @param object $event
     * @return void
     */
    public function execute(object $event): void
    {
        if (
            !($event instanceof ConsoleCommandEvent) ||
            !($event->getCommand() instanceof StartCommand)
        ) {
            return;
        }

        $output = $event->getOutput();

        if ($this->environment->isProduction()) {
            $output->writeln('Production environment detected.');
            $output->writeln('Generating ORM Proxies.');
            $command = $this->application->find('orm:generate-proxies');
            $returnCode = $command->run(new ArrayInput([]), $output);
            if ($returnCode !== 0) {
                $event->stopPropagation();
                $event->disableCommand();
                $output->writeln('Failed to generate ORM Proxies.');
                return;
            }
        }

        if ($this->migrationsCheck->execute()) {
            $output->writeln('Running database migrations.');
            $command = $this->application->find('migrations:migrate');
            $returnCode = $command->run(new ArrayInput([
                '--no-interaction' => true,
            ]), $output);
            if ($returnCode !== 0) {
                $event->stopPropagation();
                $event->disableCommand();
                $output->writeln('Failed to run database migrations.');
                return;
            }
        }
    }
}
