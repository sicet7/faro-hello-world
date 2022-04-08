<?php

namespace App\Console\Listeners;

use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;
use Sicet7\Faro\Swoole\Events\ServerStartEvent;
use Sicet7\Faro\Swoole\Events\ServerStopEvent;

#[ListensTo(ServerStartEvent::class), ListensTo(ServerStopEvent::class)]
class ServerListener implements ListenerInterface
{
    /**
     * @param object $event
     * @return void
     */
    public function execute(object $event): void
    {
        /** @var ServerStartEvent|ServerStopEvent $event */
        if ($event instanceof ServerStartEvent) {
            $server = $event->getServer();
            echo 'Server started listening on: ' . $server->host . ':' . $server->port . PHP_EOL;
        } else {
            echo 'Server is shutting down.' . PHP_EOL;
        }
    }
}
