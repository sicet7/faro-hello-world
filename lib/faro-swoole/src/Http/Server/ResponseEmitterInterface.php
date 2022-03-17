<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    /**
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void;
}
