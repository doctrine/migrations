<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

interface MigrationFinder
{
    /** @return string[] */
    public function findMigrations(string $directory) : array;
}
