<?php

namespace Sicet7\Faro\Swoole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    public const DEFAULT_PORT = 5000;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addArgument(
            'ip_and_port',
            InputArgument::REQUIRED,
            'The ip and the port that the server should listen on',
            '0.0.0.0:' . self::DEFAULT_PORT
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $ipAndPort = $input->getArgument('ip_and_port');
        if (!$this->validateIpAndPort($ipAndPort)) {
            throw new RuntimeException('Invalid IP for Port.');
        }
        $ip = $this->getIp($ipAndPort);
        $port = $this->getPort($ipAndPort);
        $output->writeln('Hello');
        return 0;
    }

    public function getIp(string $ipAndPort): string
    {
        if (substr_count($ipAndPort, ':') === 1) {
            $parts = explode(':', $ipAndPort);
            return $parts[0];
        }
        return $ipAndPort;
    }

    public function getPort(string $ipAndPort): int
    {
        if (substr_count($ipAndPort, ':') === 1) {
            $parts = explode(':', $ipAndPort);
            return (int) $parts[1];
        }
        return self::DEFAULT_PORT;
    }

    public function validateIpAndPort(string $ipAndPort): bool
    {
        if (substr_count($ipAndPort, ':') === 1) {
            $parts = explode(':', $ipAndPort);
            $ip = $parts[0];
            $port = $parts[1];
        } else {
            $ip = $ipAndPort;
            $port = self::DEFAULT_PORT;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!is_numeric($port) || $port < 0 || $port > 65565) {
            return false;
        }

        return true;
    }
}
