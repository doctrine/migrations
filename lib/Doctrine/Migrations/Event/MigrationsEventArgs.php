<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;

/**
 * The MigrationEventsArgs class is passed to events not related to a single migration version.
 */
final class MigrationsEventArgs extends EventArgs
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MigrationPlanList $plan,
        private readonly MigratorConfiguration $migratorConfiguration,
    ) {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getPlan(): MigrationPlanList
    {
        return $this->plan;
    }

    public function getMigratorConfiguration(): MigratorConfiguration
    {
        return $this->migratorConfiguration;
    }
}
