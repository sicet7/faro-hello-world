<?php

namespace App\Web\Routes;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sicet7\Faro\Core\ModuleList;
use Sicet7\Faro\Slim\Attributes\Routing\Any;

#[Any('/hello')]
class HelloWorld
{
    /**
     * @param ResponseInterface $response
     * @param ModuleList $list
     * @return ResponseInterface
     */
    public function __invoke(ResponseInterface $response, ModuleList $list, LoggerInterface $logger): ResponseInterface
    {
//        trigger_deprecation('test', '1.0.0', 'dont do this');
        $response->getBody()->write('<pre>' . var_export($list->getDefinedObjects(), true) . '</pre>');
        return $response;
    }
}
