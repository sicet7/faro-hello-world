<?php

namespace Server\App\Web\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Server\App\Web\Traits\BodyParsingTrait;
use Sicet7\Faro\Core\Attributes\Definition;
use Slim\Exception\HttpBadRequestException;

#[Definition]
class JsonBodyParsingMiddleware extends Middleware
{
    use BodyParsingTrait;

    public const TYPE = 'application/json';

    /**
     * @var bool
     */
    private bool $requireValidBody;

    /**
     * @var int
     */
    private int $depth;

    /**
     * @param bool $requireValidBody
     * @param int $depth
     */
    public function __construct(bool $requireValidBody = false, int $depth = 512)
    {
        $this->requireValidBody = $requireValidBody;
        $this->depth = $depth;
    }

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
        return $handler->handle($request->withParsedBody($this->parseBody($request)));
    }

    /**
     * @param ServerRequestInterface $request
     * @return array|null
     * @throws HttpBadRequestException
     */
    protected function parseBody(ServerRequestInterface $request): ?array
    {
        $body = $request->getBody();
        $body->rewind();
        $content = $body->getContents();
        $body->rewind();

        $flags = 0;

        if ($this->requireValidBody) {
            $flags = $flags | JSON_THROW_ON_ERROR;
        }

        try {
            $data = json_decode($content, true, $this->depth, $flags);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return null;
            }
            return $data;
        } catch (\JsonException $jsonException) {
            throw new HttpBadRequestException($request, 'Invalid JSON in request body.', $jsonException);
        }
    }
}
