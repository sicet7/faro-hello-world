<?php

namespace Server\App\Web\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Server\App\Web\Providers\BodyParserProvider;

class BodyParsingMiddleware extends \Slim\Middleware\BodyParsingMiddleware
{
    /**
     * @param BodyParserProvider $bodyParserProvider
     */
    public function __construct(BodyParserProvider $bodyParserProvider)
    {
        $this->bodyParsers = $bodyParserProvider;
        parent::__construct([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->process($request, $handler);
    }

    /**
     * @param string $mediaType
     * @param string $resolvable
     * @return $this
     */
    public function registerResolvableBodyParser(string $mediaType, string $resolvable): self
    {
        $this->bodyParsers[$mediaType] = $resolvable;
        return $this;
    }
}
