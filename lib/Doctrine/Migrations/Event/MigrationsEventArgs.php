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
    /** @var Connection */
    private $connection;

    /** @var MigrationPlanList */
    private $plan;

    /** @var MigratorConfiguration */
    private $migratorConfiguration;

    public function __construct(
        Connection $connection,
        MigrationPlanList $plan,
        MigratorConfiguration $migratorConfiguration
    ) {
        $this->connection            = $connection;
        $this->plan                  = $plan;
        $this->migratorConfiguration = $migratorConfiguration;
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
