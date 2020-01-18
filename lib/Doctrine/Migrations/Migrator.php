<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Query\Query;

/**
 * The Migrator interface is responsible for generating and executing the SQL for a migration.
 *
 * @internal
 */
interface Migrator
{
    /**
     * @return array<string, Query[]> A list of SQL statements executed, grouped by migration version
     */
    public function migrate(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration) : array;
}
