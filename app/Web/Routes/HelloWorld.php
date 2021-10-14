<?php

namespace App\Web\Routes;

use Psr\Http\Message\ResponseInterface;
use Sicet7\Faro\Slim\Attributes\Routing\Any;

#[Any('/')]
class HelloWorld
{
    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        trigger_deprecation('test', '1.0.0', 'dont do this');
        $response->getBody()->write('Hello World');
        return $response;
    }
}
