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
    /** @var Connection */
    private $connection;

    /** @var MigrationPlan */
    private $plan;

    /** @var MigratorConfiguration */
    private $migratorConfiguration;

    public function __construct(
        Connection $connection,
        MigrationPlan $plan,
        MigratorConfiguration $migratorConfiguration
    ) {
        $this->connection            = $connection;
        $this->plan                  = $plan;
        $this->migratorConfiguration = $migratorConfiguration;
    }

    public function getConnection() : Connection
    {
        return $this->connection;
    }

    public function getPlan() : MigrationPlan
    {
        return $this->plan;
    }

    public function getMigratorConfiguration() : MigratorConfiguration
    {
        return $this->migratorConfiguration;
    }
}
