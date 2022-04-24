<?php

namespace Sicet7\Faro\Swoole\Commands;

use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Core\Attributes\Name;
use Sicet7\Faro\Swoole\Exceptions\SwooleException;
use Sicet7\Faro\Swoole\Http\Server\Initializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Name('swoole:server:start')]
class StartCommand extends Command
{
    public const DEFAULT_PORT = 5000;

    /**
     * @var Initializer
     */
    private Initializer $initializer;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * StartCommand constructor.
     * @param Initializer $initializer
     * @param Config $config
     * @param string|null $name
     */
    public function __construct(
        Initializer $initializer,
        Config $config,
        string $name = null
    ) {
        parent::__construct($name);
        $this->initializer = $initializer;
        $this->config = $config;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument(
            'ip_and_port',
            InputArgument::OPTIONAL,
            'The ip and the port that the server should listen on'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws SwooleException|RuntimeException
     * @return int
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $ip = $this->config->find('server.ip');
        $port = $this->config->find('server.port');
        if ($ip === null || $port === null) {
            $ipAndPort = $input->getArgument('ip_and_port');
            if (!$this->validateIpAndPort($ipAndPort)) {
                throw new RuntimeException('Invalid IP for Port.');
            }
            $ip = $this->getIp($ipAndPort);
            $port = $this->getPort($ipAndPort);
        }
        $this->initializer->init(
            $ip,
            $port,
            $this->config->find('server.ssl.enabled', false)
        );
        $this->initializer->configure($this->config);
        $this->initializer->start();
        return 0;
    }

    /**
     * @param string $ipAndPort
     * @return string
     */
    public function getIp(string $ipAndPort): string
    {
        if (substr_count($ipAndPort, ':') === 1) {
            $parts = explode(':', $ipAndPort);
            return $parts[0];
        }
        return $ipAndPort;
    }

    /**
     * @param string $ipAndPort
     * @return int
     */
    public function getPort(string $ipAndPort): int
    {
        if (substr_count($ipAndPort, ':') === 1) {
            $parts = explode(':', $ipAndPort);
            return (int) $parts[1];
        }
        return self::DEFAULT_PORT;
    }

    /**
     * @param string $ipAndPort
     * @return bool
     */
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
