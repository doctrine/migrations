<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\MigratorConfiguration;

/**
 * The MigrationsVersionEventArgs class is passed to events related to a single migration version.
 */
final class MigrationsVersionEventArgs extends EventArgs
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MigrationPlan $plan,
        private readonly MigratorConfiguration $migratorConfiguration,
    ) {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getPlan(): MigrationPlan
    {
        return $this->plan;
    }

    public function getMigratorConfiguration(): MigratorConfiguration
    {
        return $this->migratorConfiguration;
    }
}
