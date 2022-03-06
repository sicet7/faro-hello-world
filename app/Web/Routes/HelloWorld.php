<?php

namespace App\Web\Routes;

use Psr\Http\Message\ResponseInterface;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Slim\Attributes\Routing\Any;

#[Any('/')]
class HelloWorld
{
    /**
     * @param ResponseInterface $response
     * @param ModuleList $list
     * @return ResponseInterface
     */
    public function __invoke(ResponseInterface $response, ModuleList $list): ResponseInterface
    {
//        trigger_deprecation('test', '1.0.0', 'dont do this');
        $response->getBody()->write('<pre>' . var_export($list->getDefinedObjects(), true) . '</pre>');
        return $response;
    }
}
