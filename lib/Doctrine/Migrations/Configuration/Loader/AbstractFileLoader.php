<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;

/**
 * The ArrayConfiguration class is responsible for loading migration configuration information from a PHP file.
 *
 * @internal
 */
abstract class AbstractFileLoader implements Loader
{
    protected function getDirectoryRelativeToFile(string $file, array $input): array
    {
        foreach ($input as $ns => $dir) {
            $path = realpath(dirname($file) . '/' . $dir);

            $input[$ns] = $path !== false ? $path : $dir;
        }
        return $input;
    }
}
