<?php

namespace Server\App\Web\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Server\App\Web\Traits\BodyParsingTrait;
use Sicet7\Faro\Core\Attributes\Definition;

#[Definition]
class JsonBodyParsingMiddleware extends Middleware
{
    use BodyParsingTrait;

    public const TYPE = 'application/json';

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if (!empty($parsedBody) || !$this->shouldParseRequestBodyAsType($request, self::TYPE)) {
            return $handler->handle($request);
        }
        return $handler->handle($request->withParsedBody($this->parseBody($request->getBody())));
    }

    /**
     * @param StreamInterface $body
     * @return mixed
     */
    protected function parseBody(StreamInterface $body): mixed
    {
        $body->rewind();
        $content = $body->getContents();
        $body->rewind();

    }
}
