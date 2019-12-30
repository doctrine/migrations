<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use function dirname;
use function realpath;

/**
 * @internal
 */
abstract class AbstractFileLoader implements Loader
{
    /**
     * @param array<string,string> $input
     *
     * @return array<string,string>
     */
    final protected function getDirectoryRelativeToFile(string $file, array $input) : array
    {
        foreach ($input as $ns => $dir) {
            $path = realpath(dirname($file) . '/' . $dir);

            $input[$ns] = $path !== false ? $path : $dir;
        }

        return $input;
    }
}
