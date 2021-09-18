<?php

namespace Sicet7\Faro\Slim\Listeners;

use Sicet7\Faro\Event\Attributes\ListensTo;
use Sicet7\Faro\Event\Interfaces\ListenerInterface;
use Sicet7\Faro\Web\RequestEvent;
use Slim\App;

#[ListensTo(RequestEvent::class)]
class RequestListener implements ListenerInterface
{
    /**
     * @var App
     */
    private App $slimApplication;

    /**
     * RequestListener constructor.
     * @param App $slimApplication
     */
    public function __construct(App $slimApplication)
    {
        $this->slimApplication = $slimApplication;
    }

    /**
     * @param object $event
     * @return void
     */
    public function execute(object $event): void
    {
        if ($event instanceof RequestEvent) {
            $event->setResponse($this->slimApplication->handle($event->getRequest()));
        }
    }
}
