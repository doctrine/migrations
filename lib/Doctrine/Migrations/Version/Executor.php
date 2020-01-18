<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Query\Query;

/**
 * The Executor defines the interface used for adding sql for a migration and executing that sql.
 *
 * @internal
 */
interface Executor
{
    public function addSql(Query $sqlQuery) : void;

    public function execute(MigrationPlan $plan, MigratorConfiguration $migratorConfiguration) : ExecutionResult;
}
