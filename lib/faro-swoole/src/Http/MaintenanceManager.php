<?php

namespace Sicet7\Faro\Swoole\Http;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Swoole\Http\Server\RunnerInterface;
use Sicet7\Faro\Swoole\Http\Server\WorkerState;

class MaintenanceManager
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var WorkerState
     */
    private WorkerState $workerState;

    /**
     * MaintenanceManager constructor.
     * @param Config $config
     * @param WorkerState $workerState
     */
    public function __construct(Config $config, WorkerState $workerState)
    {
        $this->config = $config;
        $this->workerState = $workerState;
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return WorkerState
     */
    protected function getWorkerState(): WorkerState
    {
        return $this->workerState;
    }

    /**
     * @return string|null
     */
    protected function getAppRoot(): ?string
    {
        return $this->getConfig()->find('dir.root');
    }

    /**
     * @return bool
     */
    public function isFlagSet(): bool
    {
        $appRoot = $this->getAppRoot();
        if ($appRoot === null) {
            return false;
        }
        return file_exists($appRoot . '/maintenance.flag');
    }

    /**
     * @return string
     */
    protected function getFlagContent(): string
    {
        $appRoot = $this->getAppRoot();
        if ($appRoot === null) {
            return RunnerInterface::ERROR_DESC[503];
        }
        $errorFile = $appRoot . '/maintenance.flag';
        if (file_exists($errorFile) && ($content = file_get_contents($errorFile)) !== false) {
            return $content;
        }
        return RunnerInterface::ERROR_DESC[503];
    }

    /**
     * @return bool
     */
    protected function isIpWhitelisted(): bool
    {
        // TODO: handles proxy requests?.
        $config = $this->getConfig();
        $request = $this->getWorkerState()->getRequest();
        return in_array($request->server['remote_addr'], $config->find('maintenance.whitelist.ips', []));
    }

    /**
     * @return bool
     */
    protected function hasWhitelistCookie(): bool
    {
        $config = $this->getConfig();
        $request = $this->getWorkerState()->getRequest();
        $whitelistCookies = $config->find('maintenance.whitelist.cookies', []);
        if (empty($whitelistCookies)) {
            return false;
        }
        $cookies = $request->cookie;
        foreach ($cookies as $cookieName => $cookieValue) {
            if (
                array_key_exists($cookieName, $whitelistCookies) &&
                $whitelistCookies[$cookieName] == $cookieValue
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return void
     */
    protected function displayMaintenance(): void
    {
        $retryTime = $this->getConfig()->find('maintenance.retry_interval', 3600);
        $this->getWorkerState()->getResponse()->setHeader('Retry-After', $retryTime);
        $this->getWorkerState()->getResponse()->setStatusCode(503, RunnerInterface::ERRORS[503]);
        $this->getWorkerState()->getResponse()->end($this->getFlagContent());
    }

    /**
     * @return bool
     */
    public function maintenanceCheck(): bool
    {
        if (!$this->isFlagSet()) {
            return false;
        }
        if ($this->isIpWhitelisted()) {
            return false;
        }
        if ($this->hasWhitelistCookie()) {
            return false;
        }
        $this->displayMaintenance();
        return true;
    }
}
