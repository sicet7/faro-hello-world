<?php

namespace Sicet7\Faro\Swoole\Http;

use Psr\Http\Message\ServerRequestInterface;

interface ServerRequestBuilderInterface
{
    /**
     * @return ServerRequestInterface
     */
    public function build(): ServerRequestInterface;
}
