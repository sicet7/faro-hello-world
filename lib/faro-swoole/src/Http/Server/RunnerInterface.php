<?php

namespace Sicet7\Faro\Swoole\Http\Server;

use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

interface RunnerInterface
{
    public const ERRORS = [
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
    ];

    public const ERROR_DESC = [
        500 => 'The server encountered an unexpected condition that prevented it from fulfilling the request.',
        501 => 'The server does not support the functionality required to fulfill the request.',
        502 => 'The server, while acting as a gateway or proxy, received an' .
            ' invalid response from an inbound server it accessed while attempting to fulfill the request.',
        503 => 'The server is currently unable to handle the request due to a temporary' .
            ' overload or scheduled maintenance, which will likely be alleviated after some delay.',
        504 => 'The server, while acting as a gateway or proxy, did not receive a timely response' .
            ' from an upstream server it needed to access in order to complete the request.',
        505 => 'The server does not support, or refuses to support, the major version of' .
            ' HTTP that was used in the request message.',
        506 => 'The server has an internal configuration error: the chosen variant resource is configured to engage' .
            ' in transparent content negotiation itself, and is therefore not a proper end point in' .
            ' the negotiation process.',
        507 => 'The method could not be performed on the resource because the server is unable to store the' .
            ' representation needed to successfully complete the request.',
        508 => 'The server terminated an operation because it encountered an infinite loop while processing a' .
            ' request with "Depth: infinity". This status indicates that the entire operation failed.',
    ];

    /**
     * @return EventDispatcherInterface
     */
    public function getConsoleDispatcher(): EventDispatcherInterface;

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart(Server $server, int $workerId): void;

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response): void;

    /**
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStop(Server $server, int $workerId): void;
}
