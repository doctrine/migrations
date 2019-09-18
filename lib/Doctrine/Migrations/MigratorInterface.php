<?php

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Version\Version;

/**
 * The Migrator class is responsible for generating and executing the SQL for a migration.
 *
 * @internal
 */
interface MigratorInterface
{
    public function migrate(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration);
}
