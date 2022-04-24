<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Psr\Log\LoggerInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Config\Exceptions\ConfigException;
use Sicet7\Faro\Config\Exceptions\ConfigNotFoundException;

class ErrorHandler extends \Monolog\ErrorHandler
{
    public const ERROR = 'Internal Server Error';

    public const ERROR_DESC = 'The server encountered an unexpected condition that prevented' .
    ' it from fulfilling the request.';

    private const LEVEL_MAP_KEY = 'error.handler.levelMap';

    private const FETAL_RES_MEM_SIZE_KEY = 'error.handler.fetal.reservedMemorySize';
    private const FETAL_LOG_LEVEL_KEY = 'error.handler.fetal.logLevel';

    /**
     * @var WorkerState|null
     */
    private ?WorkerState $workerState = null;

    /**
     * @param LoggerInterface $logger
     * @param WorkerState $state
     * @param Config $config
     * @return static
     */
    public static function create(
        LoggerInterface $logger,
        WorkerState $state,
        Config $config
    ): static {
        $handler = new static($logger);
        $handler->setWorkerState($state);
        $levelMap = $config->find(self::LEVEL_MAP_KEY, []);
        $fetalResMemSize = $config->find(self::FETAL_RES_MEM_SIZE_KEY, 20);
        $fetalLogLevel = $config->find(self::FETAL_LOG_LEVEL_KEY);
        $handler->registerErrorHandler(
            $levelMap,
            !str_contains($config->find('app.env', 'production'), 'prod'),
            -1,
            false
        );
        $handler->registerFatalHandler($fetalLogLevel, $fetalResMemSize);
        return $handler;
    }

    /**
     * @return void
     */
    public function handleFatalError(): void
    {
        parent::handleFatalError();
        $this->sendErrorResponse();
    }

    /**
     * @return void
     */
    public function sendErrorResponse(): void
    {
        $this->getWorkerState()?->getResponse()?->setStatusCode(500, self::ERROR);
        $this->getWorkerState()?->getResponse()?->end(self::ERROR_DESC);
    }

    /**
     * @return WorkerState|null
     */
    public function getWorkerState(): ?WorkerState
    {
        return $this->workerState;
    }

    /**
     * @param WorkerState|null $state
     * @return $this
     */
    public function setWorkerState(?WorkerState $state): self
    {
        $this->workerState = $state;
        return $this;
    }

    /**
     * @return void
     */
    public function bootMessage(): void
    {
        $workerId = $this->getWorkerState()?->getId();
        if (is_int($workerId)) {
            echo '"' . static::class . '" Booted on Worker #' . $workerId . PHP_EOL;
        } else {
            echo '"' . static::class . '" Booted on Unknown Worker' . PHP_EOL;
        }
    }
}
