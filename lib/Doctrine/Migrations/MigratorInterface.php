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
     * @return array<string, string[]> A list of SQL statements executed, grouped by migration version
     */
    public function migrate(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration) : array;
}
