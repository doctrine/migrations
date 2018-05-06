<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use function glob;
use function rtrim;

final class GlobFinder extends Finder
{
    /**
     * @return string[]
     */
    public function findMigrations(string $directory, ?string $namespace = null) : array
    {
        $dir = $this->getRealPath($directory);

        $files = glob(rtrim($dir, '/') . '/Version*.php');

        return $this->loadMigrations($files, $namespace);
    }
}
