<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Query\Query;

/**
 * The MigrationsQueryEventArgs class is passed to events related to a single migration query from a version.
 */
final class MigrationsQueryEventArgs extends EventArgs
{
    /** @var Connection */
    private $connection;

    /** @var MigrationPlan */
    private $plan;

    /** @var MigratorConfiguration */
    private $migratorConfiguration;

    /** @var Query */
    private $query;

    public function __construct(
        Connection $connection,
        MigrationPlan $plan,
        MigratorConfiguration $migratorConfiguration,
        Query $query
    ) {
        $this->connection            = $connection;
        $this->plan                  = $plan;
        $this->migratorConfiguration = $migratorConfiguration;
        $this->query                 = $query;
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

    public function getQuery() : Query
    {
        return $this->query;
    }
}
