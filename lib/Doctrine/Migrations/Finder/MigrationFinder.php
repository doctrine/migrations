<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

interface MigrationFinder
{
    /**
     * @param string $directory The directory which the finder should search
     * @param string|null $namespace If not null only classes in this namespace will be returned
     * @return string[]
     */
    public function findMigrations(string $directory, ?string $namespace = null) : array;
}
