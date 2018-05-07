<?php

namespace Doctrine\Migrations;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\Migrations\Version;

final class EventDispatcher
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    public function __construct(Configuration $configuration, Connection $connection)
    {
        $this->configuration = $configuration;
        $this->connection = $connection;
    }

    public function dispatchMigrationEvent(string $eventName, string $direction, bool $dryRun) : void
    {
        $event = $this->createMigrationEventArgs($direction, $dryRun);

        $this->dispatchEvent($eventName, $event);
    }

    public function dispatchVersionEvent(
        Version $version,
        string $eventName,
        string $direction,
        bool $dryRun
    ) : void {
        $event = $this->createMigrationsVersionEventArgs(
            $version,
            $direction,
            $dryRun
        );

        $this->dispatchEvent($eventName, $event);
    }

    public function dispatchEvent(string $eventName, ?EventArgs $args = null) : void
    {
        $this->connection->getEventManager()->dispatchEvent($eventName, $args);
    }

    private function createMigrationEventArgs(string $direction, bool $dryRun) : MigrationsEventArgs
    {
        return new MigrationsEventArgs($this->configuration, $direction, $dryRun);
    }

    private function createMigrationsVersionEventArgs(
        Version $version,
        string $direction,
        bool $dryRun
    ) : MigrationsVersionEventArgs {
        return new MigrationsVersionEventArgs(
            $version,
            $this->configuration,
            $direction,
            $dryRun
        );
    }
}
