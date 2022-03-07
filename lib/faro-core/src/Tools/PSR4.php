<?php

namespace Sicet7\Faro\Core\Tools;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PSR4
{
    /**
     * @param string $namespace
     * @param string $directory
     * @return array
     */
    public static function getFQCNs(string $namespace, string $directory): array
    {
        if (!file_exists($directory)) {
            return [];
        }
        $namespace = trim($namespace, " \t\n\r\0\x0B\\");
        $output = [];
        foreach (
            Finder::create()
                ->ignoreDotFiles(true)
                ->ignoreVCS(true)
                ->files()
                ->in($directory)
                ->name('*.php') as $file
        ) {
            /** @var SplFileInfo $file */
            $output[] = $namespace . '\\' .
                str_replace(
                    '/',
                    '\\',
                    trim(strstr($file->getRelativePathname(), '.', true), " \t\n\r\0\x0B/")
                );
        }
        return $output;
    }
}
