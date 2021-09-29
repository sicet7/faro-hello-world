<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Psr\Log\LoggerInterface;

class ErrorHandler extends \Monolog\ErrorHandler
{
    public const ERROR = 'Internal Server Error';

    public const ERROR_DESC = 'The server encountered an unexpected condition that prevented' .
    ' it from fulfilling the request.';

    /**
     * @var WorkerState|null
     */
    private ?WorkerState $workerState = null;

    /**
     * @param LoggerInterface $logger
     * @param array $errorLevelMap
     * @param array $exceptionLevelMap
     * @param null $fatalLevel
     * @param WorkerState|null $workerState
     * @return static
     */
    public static function register(
        LoggerInterface $logger,
        $errorLevelMap = [],
        $exceptionLevelMap = [],
        $fatalLevel = null,
        ?WorkerState $workerState = null
    ): ErrorHandler {
        return parent::register($logger, $errorLevelMap, $exceptionLevelMap, $fatalLevel)->setWorkerState($workerState);
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
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @return bool
     */
    public function handleError(
        int $code,
        string $message,
        string $file = '',
        int $line = 0,
        array $context = []
    ): bool {
        $result = parent::handleError($code, $message, $file, $line, $context);
        $this->sendErrorResponse();
        return $result;
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
        echo '"' . static::class . '" Booted!' . PHP_EOL;
    }
}
