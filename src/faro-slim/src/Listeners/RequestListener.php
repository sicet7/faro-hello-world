<?php

namespace Sicet7\Faro\Slim\Listeners;

use Sicet7\Faro\Core\Event\ListenerInterface;
use Sicet7\Faro\Web\RequestEvent;
use Slim\App;

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
     */
    public function execute(object $event): void
    {
        if ($event instanceof RequestEvent) {
            $event->setResponse($this->slimApplication->handle($event->getRequest()));
        }
    }
}
