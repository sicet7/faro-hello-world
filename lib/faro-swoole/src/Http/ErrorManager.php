<?php

namespace Sicet7\Faro\Swoole\Http;

use Psr\Container\ContainerInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Swoole\Http\Server\WorkerState;

class ErrorManager
{
    public const ERRORS = [
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
    ];

    public const ERROR_DESC = [
        500 => 'The server encountered an unexpected condition that prevented it from fulfilling the request.',
        501 => 'The server does not support the functionality required to fulfill the request.',
        502 => 'The server, while acting as a gateway or proxy, received an' .
            ' invalid response from an inbound server it accessed while attempting to fulfill the request.',
        503 => 'The server is currently unable to handle the request due to a temporary' .
            ' overload or scheduled maintenance, which will likely be alleviated after some delay.',
        504 => 'The server, while acting as a gateway or proxy, did not receive a timely response' .
            ' from an upstream server it needed to access in order to complete the request.',
        505 => 'The server does not support, or refuses to support, the major version of' .
            ' HTTP that was used in the request message.',
        506 => 'The server has an internal configuration error: the chosen variant resource is configured to engage' .
            ' in transparent content negotiation itself, and is therefore not a proper end point in' .
            ' the negotiation process.',
        507 => 'The method could not be performed on the resource because the server is unable to store the' .
            ' representation needed to successfully complete the request.',
        508 => 'The server terminated an operation because it encountered an infinite loop while processing a' .
            ' request with "Depth: infinity". This status indicates that the entire operation failed.',
    ];

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * MaintenanceManager constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->getContainer()->get(Config::class);
    }

    /**
     * @return WorkerState
     */
    protected function getWorkerState(): WorkerState
    {
        return $this->getContainer()->get(WorkerState::class);
    }

    /**
     * @return string|null
     */
    protected function getAppRoot(): ?string
    {
        try {
            return $this->getConfig()->get('dir.root');
        } catch (ConfigException $configException) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    protected function getIncludeRoot(): ?string
    {
        try {
            return $this->getConfig()->get('dir.include');
        } catch (ConfigException $configException) {
            return null;
        }
    }

    /**
     * @return bool
     */
    protected function isFlagSet(): bool
    {
        $appRoot = $this->getAppRoot();
        if ($appRoot === null) {
            return false;
        }
        return file_exists($appRoot . '/maintenance.flag');
    }

    /**
     * @return bool
     */
    protected function isIpWhitelisted(): bool
    {
        try {
            $config = $this->getConfig();
            $request = $this->getWorkerState()->getRequest();
            return $config->has('maintenance.whitelist.ips') &&
                in_array($request->server['remote_addr'], $config->get('maintenance.whitelist.ips'));
        } catch (ConfigException $configException) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function hasWhitelistCookie(): bool
    {
        try {
            $config = $this->getConfig();
            $request = $this->getWorkerState()->getRequest();
            if (!$config->has('maintenance.whitelist.cookies')) {
                return false;
            }
            $whitelistCookies = $config->get('maintenance.whitelist.cookies');
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
        } catch (ConfigException $configException) {
            return false;
        }
    }

    /**
     * @param int $code
     * @return void
     */
    public function displayError(int $code): void
    {
        $appRoot = $this->getIncludeRoot();
        if ($appRoot === null) {
            return;
        }
        $errorFile = $appRoot . '/errors/' . $code . '.html';
        $retryTime = ($this->getConfig()->has('maintenance.retry_interval') ?
            $this->getConfig()->get('maintenance.retry_interval') : 3600);
        $this->getWorkerState()->getResponse()->setHeader('Retry-After', $retryTime);
        $this->getWorkerState()->getResponse()->setStatusCode($code, self::ERRORS[$code]);
        if (file_exists($errorFile) && ($content = file_get_contents($errorFile)) !== false) {
            $this->getWorkerState()->getResponse()->end($content);
        } else {
            $this->getWorkerState()->getResponse()->end(self::ERROR_DESC[$code]);
        }
    }

    /**
     * @return bool
     */
    public function inMaintenance(): bool
    {
        try {
            if (!$this->isFlagSet()) {
                return false;
            }
            if ($this->isIpWhitelisted()) {
                return false;
            }
            if ($this->hasWhitelistCookie()) {
                return false;
            }
            $this->displayError(503);
            return true;
        } catch (ConfigException $configException) {
            $this->displayError(503);
            return true;
        }
    }
}
