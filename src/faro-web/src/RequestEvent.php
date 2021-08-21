<?php

namespace Sicet7\Faro\Web;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestEvent implements StoppableEventInterface
{
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $request;

    /**
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $response = null;

    /**
     * RequestEvent constructor.
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return ($this->response instanceof ResponseInterface);
    }
}
