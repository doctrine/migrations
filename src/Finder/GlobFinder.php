<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use function glob;
use function rtrim;

/**
 * The GlobFinder class finds migrations in a directory using the PHP glob() function.
 */
final class GlobFinder extends Finder
{
    /**
     * {@inheritDoc}
     */
    public function findMigrations(string $directory, string|null $namespace = null): array
    {
        $dir = $this->getRealPath($directory);

        $files = glob(rtrim($dir, '/') . '/Version*.php');
        if ($files === false) {
            $files = [];
        }

        return $this->loadMigrations($files, $namespace);
    }
}
