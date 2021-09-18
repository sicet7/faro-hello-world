<?php

namespace App\Web\Routes;

use Psr\Http\Message\ResponseInterface;
use Sicet7\Faro\Slim\Attributes\Routing\Get;

#[Get('/ping')]
class Ping
{
    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write('Pong');
        return $response;
    }
}
