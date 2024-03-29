<?php
$autoloadPaths = [
    '/autoload.php',
    '/vendor/autoload.php',
];
$loaded = false;
foreach ($autoloadPaths as $autoload) {
    for($i = 0; $i < 4; $i++) {
        $dir = __DIR__;
        if ($i > 0) {
            $dir = dirname($dir, $i);
        }
        if (file_exists($dir . $autoload)) {
            require_once $dir . $autoload;
            $loaded = true;
            break 2;
        }
    }
}

if (!$loaded) {
    throw new RuntimeException('Failed to find "autoload.php" file.');
}
unset($loaded);

use Sicet7\Faro\Console\ModuleContainer;
use Symfony\Component\Console\Application;

$container = ModuleContainer::buildContainer(\Sicet7\Faro\Console\Module::class);

$application = $container->get(Application::class);

$application->run();
