<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\MigrationPlanList;

/**
 * The Migrator class is responsible for generating and executing the SQL for a migration.
 *
 * @internal
 */
interface MigratorInterface
{
    /**
     * @return string[][]
     */
    public function migrate(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration) : array;
}
