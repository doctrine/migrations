<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * The MigratorConfiguration class is responsible for defining the configuration for a migration.
 *
 * @internal
 *
 * @see Doctrine\Migrations\DbalMigrator
 * @see Doctrine\Migrations\Version\DbalExecutor
 */
class MigratorConfiguration
{
    private bool $dryRun = false;

    private bool $timeAllQueries = false;

    private bool $noMigrationException = false;

    private bool $allOrNothing = false;

    private Schema|null $fromSchema = null;

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    public function getTimeAllQueries(): bool
    {
        return $this->timeAllQueries;
    }

    public function setTimeAllQueries(bool $timeAllQueries): self
    {
        $this->timeAllQueries = $timeAllQueries;

        return $this;
    }

    public function getNoMigrationException(): bool
    {
        return $this->noMigrationException;
    }

    public function setNoMigrationException(bool $noMigrationException = false): self
    {
        $this->noMigrationException = $noMigrationException;

        return $this;
    }

    public function isAllOrNothing(): bool
    {
        return $this->allOrNothing;
    }

    public function setAllOrNothing(bool $allOrNothing): self
    {
        $this->allOrNothing = $allOrNothing;

        return $this;
    }

    public function getFromSchema(): Schema|null
    {
        return $this->fromSchema;
    }

    public function setFromSchema(Schema $fromSchema): self
    {
        $this->fromSchema = $fromSchema;

        return $this;
    }
}
