<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\AbstractMigration;

/**
 * The MigrationFactory is responsible for creating instances of the migration class name.
 */
interface MigrationFactory
{
    public function createVersion(string $migrationClassName) : AbstractMigration;
}
