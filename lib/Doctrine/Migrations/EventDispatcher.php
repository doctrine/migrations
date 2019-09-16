<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;

/**
 * The EventDispatcher class is responsible for dispatching events internally that a user can listen for.
 *
 * @internal
 */
final class EventDispatcher
{
    /** @var Configuration */
    private $configuration;

    /** @var EventManager */
    private $eventManager;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection, Configuration $configuration, EventManager $eventManager)
    {
        $this->configuration = $configuration;
        $this->eventManager  = $eventManager;
        $this->connection    = $connection;
    }

    public function dispatchMigrationEvent(
        string $eventName,
        MigrationPlanList $migrationsPlan,
        MigratorConfiguration $migratorConfiguration
    ) : void {
        $event = $this->createMigrationEventArgs($migrationsPlan, $migratorConfiguration);

        $this->dispatchEvent($eventName, $event);
    }

    public function dispatchVersionEvent(
        string $eventName,
        MigrationPlan $plan,
        MigratorConfiguration $migratorConfiguration
    ) : void {
        $event = $this->createMigrationsVersionEventArgs(
            $plan,
            $migratorConfiguration
        );

        $this->dispatchEvent($eventName, $event);
    }

    private function dispatchEvent(string $eventName, ?EventArgs $args = null) : void
    {
        $this->eventManager->dispatchEvent($eventName, $args);
    }

    private function createMigrationEventArgs(
        MigrationPlanList $migrationsPlan,
        MigratorConfiguration $migratorConfiguration
    ) : MigrationsEventArgs {
        return new MigrationsEventArgs($this->configuration, $this->connection, $migrationsPlan, $migratorConfiguration);
    }

    private function createMigrationsVersionEventArgs(
        MigrationPlan $plan,
        MigratorConfiguration $migratorConfiguration
    ) : MigrationsVersionEventArgs {
        return new MigrationsVersionEventArgs(
            $this->configuration,
            $this->connection,
            $plan,
            $migratorConfiguration
        );
    }
}
