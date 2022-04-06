<?php

ini_set('max_execution_time', -1);
ini_set('memory_limit', -1);

const ENTRYPOINT = 'vendor/sicet7/faro-console/run.php';

const ROOT_FILES = [
    __DIR__ . '/modules.php',
];

const MAPPINGS = [
    'vendor' => __DIR__ . '/vendor',
    'app' => __DIR__ . '/app',
    'config' => __DIR__ . '/config',
];
/**
 * Don't modify beneath this line.
 */
$excludes = [];
$excludesFile = __DIR__ . '/.buildignore';

if (file_exists($excludesFile) && ($content = file_get_contents($excludesFile)) !== false) {
    $excludes = array_filter(array_map('trim', explode(PHP_EOL, trim($content))), function ($value) {
        if (!is_string($value) || empty($value) || str_starts_with($value, '#')) {
            return false;
        }
        return true;
    });
}

/**
 * @param string $command
 * @return void
 */
function runCommand(string $command): void
{
    if (passthru($command, $code) === false) {
        echo 'Command Failed: "' . $command . '"' . PHP_EOL;
        exit($code);
    }
}

try {
    $cwd = getcwd();
    chdir(__DIR__);

    runCommand('rm -rf "' . __DIR__ . '/vendor"');
    runCommand('rm -rf "' . __DIR__ . '/faro.phar"');
    runCommand('composer install --no-dev');

    $phar = new Phar(__DIR__ . '/faro.phar');

    $phar->startBuffering();

    $phar->setStub(
        '#!/usr/bin/env php' . PHP_EOL .
        '<?php Phar::mapPhar(\'faro.phar\');' . PHP_EOL .
        'require_once \'phar://faro.phar/' . ENTRYPOINT . '\';' . PHP_EOL .
        '__HALT_COMPILER();?>' . PHP_EOL
    );

    foreach (ROOT_FILES as $ROOT_FILE) {
        $phar->addFile($ROOT_FILE, basename($ROOT_FILE));
    }

    foreach (MAPPINGS as $internalRootDir => $directory) {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
        ));
        $prefix = rtrim($directory, " \t\n\r\0\x0B/") . '/';
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isLink() || !str_starts_with($file->getPathname(), $prefix)) {
                continue;
            }
            $internalPath = $internalRootDir . '/' . ltrim(substr($file->getPathname(), strlen($prefix)), " \t\n\r\0\x0B/");
            foreach ($excludes as $regex) {
                if (preg_match($regex, $internalPath) === 1) {
                    continue 2;
                }
            }
            $phar->addFile($file->getPathname(), $internalPath);
            echo 'Building "' . $internalPath . '".' . PHP_EOL;
        }
    }

    $phar->stopBuffering();

    $phar->compressFiles(Phar::GZ);

    chdir($cwd);

} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}

