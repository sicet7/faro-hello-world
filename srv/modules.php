<?php

use Sicet7\Faro\Core\Tools\ModuleRegistration;
use Sicet7\Faro\Console\ModuleContainer as ConsoleModuleContainer;
use Sicet7\Faro\Core\ModuleContainer as CoreModuleContainer;
use Sicet7\Faro\Web\ModuleContainer as WebModuleContainer;

ModuleRegistration::run([
    CoreModuleContainer::class => [
        Server\Modules\Core\CoreModule::class
    ],
    WebModuleContainer::class => [
//        App\Web\Module::class,
    ],
    ConsoleModuleContainer::class => [
        Server\Modules\DatabasePrepare\DBPrepareModule::class
    ],
]);
